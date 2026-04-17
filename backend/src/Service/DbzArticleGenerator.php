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
            'Saiyans' => [
                'subjects' => ['la fierte Saiyan', 'la rivalite Goku Vegeta', 'l heritage des guerriers Saiyans', 'la progression de Gohan', 'la colere qui declenche la puissance'],
                'focuses' => ['la pression de depasser ses limites a chaque combat', 'l impact des liens familiaux sur les decisions', 'la difference entre instinct guerrier et strategie', 'l evolution mentale des personnages dans la duree'],
            ],
            'Transformations' => [
                'subjects' => ['les transformations de DBZ', 'le passage au Super Saiyan', 'la logique des paliers de puissance', 'la maitrise du Super Saiyan 2', 'la dimension extreme du Super Saiyan 3'],
                'focuses' => ['ce que chaque forme change dans le rythme narratif', 'pourquoi les transformations marquent des ruptures psychologiques', 'le lien entre entrainement, discipline et resultat', 'l impact visuel et emotionnel sur les fans'],
            ],
            'Villains' => [
                'subjects' => ['les ennemis de DBZ', 'la menace strategique de Freezer', 'la perfection de Cell', 'le chaos de Majin Buu', 'les antagonistes qui font progresser les heros'],
                'focuses' => ['la maniere dont chaque ennemi impose une nouvelle methode de combat', 'les failles des vilains qui deviennent des leviers narratifs', 'la tension entre domination et arrogance', 'la construction d une menace de plus en plus globale'],
            ],
            'Saga Cell' => [
                'subjects' => ['la saga Cell', 'l arc des Cell Games', 'la montee en puissance de Gohan', 'les erreurs de Vegeta face a Cell', 'le sacrifice de Goku contre Cell'],
                'focuses' => ['l importance des choix individuels dans l issue du conflit', 'la transition entre generation Goku et generation Gohan', 'l equilibre entre drame familial et bataille mondiale', 'le role de la preparation avant le combat final'],
            ],
            'Saga Buu' => [
                'subjects' => ['la saga Buu', 'les formes de Majin Buu', 'la fusion Vegeto', 'la puissance du Super Saiyan 3', 'la symbolique du Genkidama final'],
                'focuses' => ['le melange entre humour, horreur et epicness', 'le role du sacrifice et de la redemption', 'la cooperation des guerriers Z dans le final', 'la facon dont Buu casse les codes des ennemis precedents'],
            ],
            'Tournois' => [
                'subjects' => ['les tournois DBZ', 'les combats eliminatoires les plus marquants', 'la discipline des arts martiaux', 'l enjeu psychologique avant un duel', 'les duels qui ont change la reputation des combattants'],
                'focuses' => ['la gestion de la pression sous les regles du tournoi', 'l intelligence tactique dans les affrontements courts', 'la lecture des styles adverses', 'l influence des tournois sur la suite des sagas'],
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

        $maxAttempts = 60;
        for ($attempt = 0; $attempt < $maxAttempts; ++$attempt) {
            $categoryName = array_rand($themes);
            $subject = $themes[$categoryName]['subjects'][array_rand($themes[$categoryName]['subjects'])];
            $focus = $themes[$categoryName]['focuses'][array_rand($themes[$categoryName]['focuses'])];
            $angle = $angles[array_rand($angles)];
            $opening = $openings[array_rand($openings)];

            $subjectKey = $this->normalizeKey($categoryName.'-'.$subject.'-'.$focus);
            if (in_array($subjectKey, $excludedSubjectKeys, true)) {
                continue;
            }

            $suffix = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
            $title = sprintf('%s: %s - %s (%s)', $opening, ucfirst($subject), $angle, $suffix);
            $content = sprintf(
                "Dragon Ball Z reste une reference quand on parle de %s.\n\n".
                "Dans cet article, on s interesse a %s. L objectif est de montrer %s.\n\n".
                "Le point cle est la construction progressive des enjeux: entrainement, depassement, puis affrontement final. ".
                "Cette structure narrative rend chaque victoire marquante et chaque defaite utile pour la suite.\n\n".
                "Conclusion: en observant %s, on comprend mieux pourquoi DBZ reste aussi influent aujourd hui.",
                $subject,
                $subject,
                $focus,
                $subject
            );

            return [
                'title' => $title,
                'content' => $content,
                'categoryName' => $categoryName,
                'picture' => null,
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

        return '' === $value ? 'dbz-subject' : $value;
    }
}
