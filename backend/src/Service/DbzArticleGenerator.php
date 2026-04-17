<?php

namespace App\Service;

class DbzArticleGenerator
{
    /**
     * @param array<int, string> $excludedSubjectKeys
     *
     * @return array{title: string, content: string, categoryName: string, picture: ?string, subjectKey: string, subject: string}|null
     */
    public function generateArticle(array $excludedSubjectKeys = []): ?array
    {
        $themes = [
            [
                'subjectKey' => 'fierte-saiyan',
                'categoryName' => 'Saiyans',
                'subject' => 'la fierte Saiyan',
                'focus' => 'comment la rivalite entre Goku et Vegeta eleve chaque saga',
            ],
            [
                'subjectKey' => 'transformations',
                'categoryName' => 'Transformations',
                'subject' => 'les transformations',
                'focus' => 'pourquoi chaque evolution change le rythme des combats',
            ],
            [
                'subjectKey' => 'ennemis-dbz',
                'categoryName' => 'Villains',
                'subject' => 'les ennemis de DBZ',
                'focus' => 'la maniere dont Freezer, Cell et Buu forcent les heros a se reinventer',
            ],
            [
                'subjectKey' => 'saga-cell',
                'categoryName' => 'Saga Cell',
                'subject' => 'la saga Cell',
                'focus' => 'la progression de Gohan vers son plein potentiel',
            ],
            [
                'subjectKey' => 'saga-buu',
                'categoryName' => 'Saga Buu',
                'subject' => 'la saga Buu',
                'focus' => 'la notion de sacrifice et d union chez les guerriers Z',
            ],
            [
                'subjectKey' => 'tournois',
                'categoryName' => 'Tournois',
                'subject' => 'les tournois',
                'focus' => 'l importance strategique des affrontements en elimination',
            ],
        ];

        $openings = [
            'Analyse DBZ',
            'Focus Dragon Ball Z',
            'Debrief Saiyan',
            'Chronique Capsule Corp',
            'Perspective Namek',
        ];

        $angles = [
            'ce que cela revele sur les personnages',
            'ce que cela change dans la narration',
            'pourquoi les fans s en souviennent encore',
            'les consequences sur la suite de la serie',
        ];

        $availableThemes = array_values(array_filter(
            $themes,
            static fn (array $theme): bool => !in_array($theme['subjectKey'], $excludedSubjectKeys, true)
        ));

        if ([] === $availableThemes) {
            return null;
        }

        $theme = $availableThemes[array_rand($availableThemes)];
        $opening = $openings[array_rand($openings)];
        $angle = $angles[array_rand($angles)];
        $suffix = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $title = sprintf('%s: %s - %s (%s)', $opening, ucfirst($theme['subject']), $angle, $suffix);

        $content = sprintf(
            "Dragon Ball Z reste une reference quand on parle de %s.\n\n".
            "Dans cet article, on s interesse a %s. L objectif est de montrer %s.\n\n".
            "Le point cle est la construction progressive des enjeux: entrainement, depassement, puis affrontement final. ".
            "Cette structure narrative rend chaque victoire marquante et chaque defaite utile pour la suite.\n\n".
            "Conclusion: en observant %s, on comprend mieux pourquoi DBZ reste aussi influent aujourd hui.",
            $theme['subject'],
            $theme['subject'],
            $theme['focus'],
            $theme['subject']
        );

        return [
            'title' => $title,
            'content' => $content,
            'categoryName' => $theme['categoryName'],
            'picture' => null,
            'subjectKey' => $theme['subjectKey'],
            'subject' => $theme['subject'],
        ];
    }
}
