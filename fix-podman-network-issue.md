# Fix Podman Network Issue

## Problem

The staging VM `u24` runs as an Incus virtual machine. The previous deployment
used rootless Podman with a custom Compose bridge network (`myapp_backend`).

On this VM, rootless bridge creation fails:

```text
netavark: create bridge: Netlink error: Operation not supported
```

That leaves containers in `Created` instead of `Running`, so PostgreSQL,
Valkey, and the rest of the application never start.

## Why It Happens

- The old stack used rootless Podman plus a user-defined `bridge` network.
- On `u24`, Podman is rootless and uses `netavark`.
- The Incus guest does not support the bridge setup path needed by that
  rootless network mode.
- `slirp4netns`, `pasta`, and `host` networking work there; `bridge` does not.

## Recommended Fix

Use a single rootless Podman pod with `pasta` networking.

This keeps rootless isolation while avoiding the unsupported rootless bridge
driver. All containers share one pod network namespace and communicate over
`127.0.0.1`.

Recommended runtime layout:

- one pod: `easygreen-pod`
- Caddy publishes `80` and optionally `443`
- app listens on `127.0.0.1:8080`
- PostgreSQL listens on `127.0.0.1:5432`
- Valkey listens on `127.0.0.1:6379`
- queue and scheduler reuse the same app image inside the pod

## Podman Pod Design

```text
easygreen-pod (network: pasta)
├── easygreen-db
├── easygreen-valkey
├── easygreen-app
├── easygreen-queue
├── easygreen-scheduler
└── easygreen-caddy
```

Benefits:

- stays rootless
- avoids broken rootless bridge creation in Incus
- keeps simple localhost service discovery
- only Caddy exposes host ports
- one pod start/stop operation controls the full stack

## Operational Changes

Old flow:

```text
podman-compose + custom bridge network + service names like db / valkey / app
```

New flow:

```text
podman pod create --network pasta
podman create --pod easygreen-pod ...
podman start ...
```

App environment changes:

- `DB_HOST=127.0.0.1`
- `REDIS_HOST=127.0.0.1`

PostgreSQL 18 note:

- mount the persistent volume at `/var/lib/postgresql`
- do not mount it at `/var/lib/postgresql/data`
- the upstream PostgreSQL 18 container layout changed, and the old mount target
  causes restart loops

Caddy upstream changes:

- old: `reverse_proxy app:8080`
- new: `reverse_proxy 127.0.0.1:8080`

## Testing Approach

Use the clean Incus snapshot before testing:

```bash
incus restore u24 snap0
```

Then run the playbooks in order:

```bash
ansible-playbook -i ansible/inventory/staging.ini ansible/harden.yml
ansible staging -i ansible/inventory/staging.ini -m reboot
ansible-playbook -i ansible/inventory/staging.ini ansible/bootstrap-pod.yml
ansible-playbook -i ansible/inventory/staging.ini ansible/deploy-pod.yml
```

Useful Incus-side checks:

```bash
incus exec u24 -- bash -lc 'podman pod ps'
incus exec u24 -- bash -lc 'podman ps -a'
incus exec u24 -- bash -lc 'podman logs easygreen-db'
incus exec u24 -- bash -lc 'podman logs easygreen-app'
incus exec u24 -- bash -lc 'curl -I http://127.0.0.1'
```

## Recommendation in Practice

- Keep production rootless.
- Prefer `pasta` for rootless networking in constrained VM environments like
  this Incus guest.
- Use one pod for tightly-coupled services that need localhost communication.
- Avoid rootless custom bridge networks here unless the underlying VM/network
  stack is proven to support them.
