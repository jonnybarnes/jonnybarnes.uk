<?php

/**
 * Automatically alias Laravel Model's to their base classname.
 * Ex: "App\Models\User" now can just be accessed by "User"
 */

if (! function_exists('aliasModels')) {
    function aliasModels() {
        $finder = new \Symfony\Component\Finder\Finder();
        $finder->files()->name('*.php')->in(base_path().'/app');
        foreach ($finder as $file) {
            $namespace = 'App\\';
            if ($relativePath = $file->getRelativePath()) {
                $namespace .= strtr($relativePath, '/', '\\') . '\\';
            }
            $class = $namespace . $file->getBasename('.php');
            try {
                $r = new \ReflectionClass($class);
                if ($r->isSubclassOf('Illuminate\\Database\\Eloquent\\Model')) {
                    class_alias($class, $file->getBasename('.php'));
                }
            } catch (Exception $e) {
                //
            }
        }
    }
}

aliasModels();

return [
    'startupMessage' => '<info>Using local config file (tinker.config.php)</info>',

    'commands' => [
        // new \App\Tinker\TestCommand,
    ],
];
