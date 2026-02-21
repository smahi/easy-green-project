    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            WilayasSeeder::class,
            BaladyasSeeder::class,
        ]);

        // Ensure a superuser exists (if not created by command)