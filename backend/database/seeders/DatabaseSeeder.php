    public function run(): void
    {
        $this->call([
            RolesSeeder::class,
            WilayasSeeder::class,
            BaladyasSeeder::class,
            ReportTypesSeeder::class,
        ]);

        // Ensure a superuser exists (if not created by command)