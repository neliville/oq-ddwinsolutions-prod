<?php

/**
 * Bundles listés en require-dev ne sont enregistrés que si le package est présent dans vendor.
 * Évite ClassNotFoundException quand APP_ENV=dev après un `composer install --no-dev` (vendor prod).
 */
$bundles = [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class => ['all' => true],
    Symfony\Bundle\SecurityBundle\SecurityBundle::class => ['all' => true],
    Doctrine\Bundle\DoctrineBundle\DoctrineBundle::class => ['all' => true],
    Doctrine\Bundle\MigrationsBundle\DoctrineMigrationsBundle::class => ['all' => true],
    Symfony\UX\StimulusBundle\StimulusBundle::class => ['all' => true],
    Symfony\UX\Turbo\TurboBundle::class => ['all' => true],
    Knp\Bundle\MenuBundle\KnpMenuBundle::class => ['all' => true],
    Symfony\UX\TwigComponent\TwigComponentBundle::class => ['all' => true],
    SymfonyCasts\Bundle\ResetPassword\SymfonyCastsResetPasswordBundle::class => ['all' => true],
    Symfony\UX\LiveComponent\LiveComponentBundle::class => ['all' => true],
    Liip\ImagineBundle\LiipImagineBundle::class => ['all' => true],
    Symfonycasts\TailwindBundle\SymfonycastsTailwindBundle::class => ['all' => true],
    Symfony\UX\React\ReactBundle::class => ['all' => true],
    Symfony\UX\Toolkit\UXToolkitBundle::class => ['all' => true],
    Symfony\UX\Icons\UXIconsBundle::class => ['all' => true],
    Twig\Extra\TwigExtraBundle\TwigExtraBundle::class => ['all' => true],
    TalesFromADev\Twig\Extra\Tailwind\Bridge\Symfony\Bundle\TalesFromADevTwigExtraTailwindBundle::class => ['all' => true],
];

if (class_exists(Symfony\Bundle\MakerBundle\MakerBundle::class)) {
    $bundles[Symfony\Bundle\MakerBundle\MakerBundle::class] = ['dev' => true];
}

if (class_exists(Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class)) {
    $bundles[Doctrine\Bundle\FixturesBundle\DoctrineFixturesBundle::class] = ['dev' => true, 'test' => true];
}

if (class_exists(DAMA\DoctrineTestBundle\DAMADoctrineTestBundle::class)) {
    $bundles[DAMA\DoctrineTestBundle\DAMADoctrineTestBundle::class] = ['test' => true];
}

if (class_exists(Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class)) {
    $bundles[Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class] = ['dev' => true, 'test' => true];
}

return $bundles;
