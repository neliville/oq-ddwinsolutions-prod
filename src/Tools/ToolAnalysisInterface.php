<?php

namespace App\Tools;

/**
 * Interface pour les données d'analyse d'un outil, utilisables par un service IA (suggestions).
 */
interface ToolAnalysisInterface
{
    public function getToolName(): string;

    /**
     * Payload à envoyer à l'IA (structure dépend du outil).
     *
     * @return array<string, mixed>
     */
    public function getPayload(): array;
}
