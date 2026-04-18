#!/usr/bin/env bash
set -e

PROJECT_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
ARTIFACT="$PROJECT_ROOT/release.tar.gz"

echo "Building artifact..."
"$PROJECT_ROOT/scripts/build.sh"

echo "Deploying..."

cd "$PROJECT_ROOT/ansible"

ansible-playbook deploy.yml \
  -i inventory/staging.ini \
  --ask-become-pass \
  --ask-vault-pass \
  --extra-vars "app_local_artifact=$ARTIFACT"

echo "Deploy finished 🚀"
