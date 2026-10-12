<?php

declare(strict_types=1);

namespace PNScripts\ProverkaLite;

use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Factory;

class ProverkaServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/proverka-lite.php', 'proverka-lite');
    }

    public function boot(): void
    {
        $this->loadTranslationsFrom(__DIR__.'/../lang', 'proverka-lite');

        if ($this->app->runningInConsole()) {
            $this->publishes([__DIR__.'/../config/proverka-lite.php' => config_path('proverka-lite.php')], 'proverka-lite-config');
            $this->publishes([__DIR__.'/../lang' => $this->app->langPath('vendor/proverka-lite')], 'proverka-lite-lang');
        }

        if (! config('proverka-lite.string_rules', true)) {
            return;
        }

        $this->callAfterResolving('validator', function (Factory $validator): void {
            foreach (StringRules::NAMES as $name) {
                $validator->extend($name, function (string $attribute, mixed $value, array $parameters) use ($name): bool {
                    return StringRules::passes($name, $parameters, $attribute, $value);
                });
                $validator->replacer($name, StringRules::replace(...));
            }
        });
    }
}
