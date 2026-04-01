<?php

declare(strict_types=1);

namespace Syscohada\Tests;

use PHPUnit\Framework\TestCase;
use Syscohada\Compte;
use Syscohada\Exceptions\CompteNotFoundException;
use Syscohada\PlanComptable;
use Syscohada\Validator;

class PlanComptableTest extends TestCase
{
    private PlanComptable $plan;

    protected function setUp(): void
    {
        PlanComptable::reset();
        $this->plan = PlanComptable::getInstance();
    }

    // -----------------------------------------------------------------------
    // find / findOrFail
    // -----------------------------------------------------------------------

    public function testFindRetourneCompteExistant(): void
    {
        $compte = $this->plan->find('6011');
        $this->assertInstanceOf(Compte::class, $compte);
        $this->assertSame('6011', $compte->getCode());
    }

    public function testFindRetourneNullSiInconnu(): void
    {
        $this->assertNull($this->plan->find('9999'));
    }

    public function testFindOrFailLanceExceptionSiInconnu(): void
    {
        $this->expectException(CompteNotFoundException::class);
        $this->plan->findOrFail('9999');
    }

    public function testFindOrFailRetourneCompte(): void
    {
        $compte = $this->plan->findOrFail('4111');
        $this->assertSame('Clients', $compte->getLibelle());
    }

    // -----------------------------------------------------------------------
    // exists / valider
    // -----------------------------------------------------------------------

    public function testExistsVraiPourCodeConnu(): void
    {
        $this->assertTrue($this->plan->exists('521'));
    }

    public function testExistsFauxPourCodeInconnu(): void
    {
        $this->assertFalse($this->plan->exists('0000'));
    }

    // -----------------------------------------------------------------------
    // classe / classeNiveau
    // -----------------------------------------------------------------------

    public function testClasseRetourneTousLesComptesDeClasse6(): void
    {
        $comptes = $this->plan->classe(6);
        $this->assertNotEmpty($comptes);
        foreach ($comptes as $c) {
            $this->assertSame(6, $c->getClasse());
        }
    }

    public function testClasseNiveauRetourneComptesCorrects(): void
    {
        $comptes = $this->plan->classeNiveau(6, 3);
        foreach ($comptes as $c) {
            $this->assertSame(6, $c->getClasse());
            $this->assertSame(3, $c->getNiveau());
        }
    }

    // -----------------------------------------------------------------------
    // enfants / descendants
    // -----------------------------------------------------------------------

    public function testEnfantsRetourneComptesCorrects(): void
    {
        $enfants = $this->plan->enfants('60');
        $codes = array_map(fn (Compte $c) => $c->getCode(), $enfants);

        $this->assertContains('601', $codes);
        $this->assertContains('602', $codes);
        $this->assertContains('603', $codes);
    }

    public function testDescendantsInclutPetitsEnfants(): void
    {
        $descendants = $this->plan->descendants('60');
        $codes = array_map(fn (Compte $c) => $c->getCode(), $descendants);

        $this->assertContains('601', $codes);   // enfant direct
        $this->assertContains('6011', $codes);  // petit-enfant
    }

    // -----------------------------------------------------------------------
    // search
    // -----------------------------------------------------------------------

    public function testSearchTrouveSurLibelle(): void
    {
        $resultats = $this->plan->search('marchandises');
        $this->assertNotEmpty($resultats);
        foreach ($resultats as $c) {
            $this->assertStringContainsStringIgnoringCase(
                'marchandise',
                $this->normaliser($c->getLibelle())
            );
        }
    }

    public function testSearchTrouveSurCode(): void
    {
        $resultats = $this->plan->search('6011');
        $this->assertCount(1, $resultats);
        $this->assertSame('6011', $resultats[0]->getCode());
    }

    public function testSearchInsensibleAuxAccents(): void
    {
        $avec    = $this->plan->search('réserves');
        $sans    = $this->plan->search('reserves');
        $this->assertNotEmpty($avec);
        $this->assertNotEmpty($sans);
    }

    // -----------------------------------------------------------------------
    // nature
    // -----------------------------------------------------------------------

    public function testNatureBilanContientClasses1A5(): void
    {
        $comptes = $this->plan->nature('bilan');
        foreach ($comptes as $c) {
            $this->assertGreaterThanOrEqual(1, $c->getClasse());
            $this->assertLessThanOrEqual(5, $c->getClasse());
        }
    }

    public function testNatureGestionContientClasses6A8(): void
    {
        $comptes = $this->plan->nature('gestion');
        foreach ($comptes as $c) {
            $this->assertGreaterThanOrEqual(6, $c->getClasse());
            $this->assertLessThanOrEqual(8, $c->getClasse());
        }
    }

    // -----------------------------------------------------------------------
    // chemin
    // -----------------------------------------------------------------------

    public function testCheminRetourneArbreComplet(): void
    {
        $chemin = $this->plan->chemin('6011');
        $codes  = array_map(fn (Compte $c) => $c->getCode(), $chemin);

        $this->assertContains('601',  $codes);
        $this->assertContains('6011', $codes);
        // Le dernier élément doit être le compte demandé
        $this->assertSame('6011', end($codes));
    }

    // -----------------------------------------------------------------------
    // statistiques
    // -----------------------------------------------------------------------

    public function testStatistiquesRetourneStructureCorrecte(): void
    {
        $stats = $this->plan->statistiques();

        $this->assertArrayHasKey('total',      $stats);
        $this->assertArrayHasKey('par_classe', $stats);
        $this->assertArrayHasKey('par_niveau', $stats);
        $this->assertArrayHasKey('par_nature', $stats);
        $this->assertGreaterThan(200, $stats['total']);
    }

    // -----------------------------------------------------------------------
    // all
    // -----------------------------------------------------------------------

    public function testAllRetourneTousLesComptes(): void
    {
        $all = $this->plan->all();
        $this->assertGreaterThan(200, count($all));
    }

    // -----------------------------------------------------------------------
    // Compte — propriétés
    // -----------------------------------------------------------------------

    public function testCompteToArray(): void
    {
        $compte = $this->plan->findOrFail('4111');
        $arr = $compte->toArray();

        $this->assertSame('4111', $arr['code']);
        $this->assertSame(4, $arr['classe']);
        $this->assertSame(4, $arr['niveau']);
        $this->assertSame('411', $arr['code_parent']);
    }

    public function testCompteToString(): void
    {
        $compte = $this->plan->findOrFail('521');
        $this->assertStringContainsString('521', (string) $compte);
    }

    // -----------------------------------------------------------------------
    // Validator
    // -----------------------------------------------------------------------

    public function testValidatorFormatValide(): void
    {
        $this->assertTrue(Validator::formatValide('6011'));
        $this->assertTrue(Validator::formatValide('60'));
        $this->assertFalse(Validator::formatValide('ABCD'));
        $this->assertFalse(Validator::formatValide('12345'));
    }

    public function testValidatorEstBilanEtGestion(): void
    {
        $this->assertTrue(Validator::estBilan('401'));
        $this->assertFalse(Validator::estBilan('601'));
        $this->assertTrue(Validator::estGestion('701'));
        $this->assertFalse(Validator::estGestion('101'));
    }

    public function testValidatorEstChargeEtProduit(): void
    {
        $this->assertTrue(Validator::estCharge('661'));
        $this->assertFalse(Validator::estCharge('771'));
        $this->assertTrue(Validator::estProduit('701'));
        $this->assertFalse(Validator::estProduit('601'));
    }

    public function testValidatorInvalides(): void
    {
        $invalides = Validator::invalides(['6011', '9999', '4111', 'XXXX']);
        $this->assertContains('9999', $invalides);
        $this->assertContains('XXXX', $invalides);
        $this->assertNotContains('6011', $invalides);
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function normaliser(string $s): string
    {
        return mb_strtolower($s, 'UTF-8');
    }
}
