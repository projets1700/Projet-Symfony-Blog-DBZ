<?php

namespace App\Service;

/**
 * Genere du contenu d analyse de l oeuvre (categorie Analyse uniquement).
 */
class WorkAnalysisArticleGenerator
{
    /**
     * @param array<int, string> $excludedSubjectKeys
     *
     * @return array{title: string, content: string, subjectKey: string, subject: string}|null
     */
    public function generateArticle(array $excludedSubjectKeys = []): ?array
    {
        $subjects = [
            'l arc narratif global de Dragon Ball Z',
            'le contraste entre humour et gravite dans DBZ',
            'la construction du suspense avant les grands combats',
            'le role de l entrainement comme theme central',
            'l evolution du heros face a la mort et au sacrifice',
            'les antagonistes comme miroirs des heros',
            'la place de la famille et des liens affectifs',
            'le passage du shonen adolescent a une epopee plus sombre',
            'la symbolique des transformations au-dela de la simple puissance',
            'le rythme narratif entre pauses et escalade des enjeux',
        ];

        $focuses = [
            'comment la serie structure l attente du spectateur',
            'ce que ces choix revelent sur les valeurs portees par l oeuvre',
            'pourquoi ces sequences restent memorables des decennies plus tard',
            'la maniere dont DBZ equilibre spectacle et emotion',
            'l impact sur la comprehension des personnages principaux',
        ];

        $angles = [
            'une lecture thematique',
            'une approche structurelle',
            'un regard sur les enjeux dramatiques',
            'une analyse du rythme scenaristique',
            'une perspective sur l univers et sa coherence',
        ];

        $openings = [
            'Analyse de l oeuvre',
            'Essai DBZ',
            'Lecture de serie',
            'Perspective critique',
            'Reflexion sur DBZ',
        ];

        $maxAttempts = 80;
        for ($attempt = 0; $attempt < $maxAttempts; ++$attempt) {
            $subject = $subjects[array_rand($subjects)];
            $focus = $focuses[array_rand($focuses)];
            $angle = $angles[array_rand($angles)];
            $opening = $openings[array_rand($openings)];

            $subjectKey = $this->normalizeKey('analysis-'.$subject.'-'.$focus);
            if (in_array($subjectKey, $excludedSubjectKeys, true)) {
                continue;
            }

            $suffix = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $title = sprintf('%s: %s - %s (%s)', $opening, ucfirst($subject), $angle, $suffix);

            $content = sprintf(
                "Cette analyse de l oeuvre Dragon Ball Z s attache a %s.\n\n".
                "On cherche a montrer %s, en s appuyant sur des moments marquants de la serie et sur la facon dont ils s enchainent.\n\n".
                "Dragon Ball Z construit souvent ses sommets narratifs autour d une tension progressive: enjeux personnels, enjeux collectifs, puis resolution par le combat. ".
                "Cette dynamique explique en partie pourquoi certaines sequences restent des references culturelles.\n\n".
                "En conclusion, en croisant %s avec %s, on obtient une lecture plus fine de ce que propose l oeuvre au-dela des affrontements.",
                $subject,
                $focus,
                $subject,
                $focus
            );

            return [
                'title' => $title,
                'content' => $content,
                'subjectKey' => $subjectKey,
                'subject' => $subject,
            ];
        }

        return null;
    }

    private function normalizeKey(string $value): string
    {
        $value = strtolower($value);
        $value = preg_replace('/[^a-z0-9]+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return '' === $value ? 'analysis-subject' : $value;
    }
}
