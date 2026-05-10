<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\Qse\CAPAAction;
use App\Entity\Qse\CapaOrigin;
use App\Entity\User;
use App\Qse\Enum\CapaStatus;
use App\Tests\TestCase\WebTestCaseWithDatabase;

final class QseCapaWorkflowFunctionalTest extends WebTestCaseWithDatabase
{
    private function reloadUserByEmail(string $email): User
    {
        $u = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
        self::assertInstanceOf(User::class, $u);

        return $u;
    }

    /**
     * Une requête GET fraîche avant chaque POST garantit un jeton CSRF aligné sur la session courante
     * (évite les 403 « Jeton CSRF invalide » après {@see KernelBrowser::loginUser()}).
     */
    private function postCapaWorkflow(string $email, int $capaId, array $fields): void
    {
        $this->client->loginUser($this->reloadUserByEmail($email));
        $crawler = $this->client->request('GET', '/dashboard/qse/capa/' . $capaId);
        $this->assertResponseIsSuccessful();
        $csrf = (string) $crawler->filter('input[name="_token"]')->first()->attr('value');
        self::assertNotSame('', $csrf);

        $this->client->request('POST', '/dashboard/qse/capa/' . $capaId, $fields + [
            '_token' => $csrf,
            'title' => 'CAPA workflow test',
        ]);
        $this->assertResponseRedirects('/dashboard/qse/capa/' . $capaId);
    }

    public function testCloseRejectedWithoutEffectivenessThenSucceedsWithVerification(): void
    {
        $email = 'capa-wf-' . uniqid() . '@example.com';
        $user = $this->createTestUser($email, 'Test123456!');
        $origin = $this->entityManager->getRepository(CapaOrigin::class)->findOneBy(['slug' => '8d']);
        self::assertInstanceOf(CapaOrigin::class, $origin);

        $capa = new CAPAAction();
        $capa->setOwner($user);
        $capa->setTitle('CAPA workflow test');
        $capa->setOrigin($origin);
        $capa->setStatus(CapaStatus::BROUILLON);
        $this->entityManager->persist($capa);
        $this->entityManager->flush();
        $id = (int) $capa->getId();

        $this->postCapaWorkflow($email, $id, ['workflow_action' => 'submit_validation']);
        $this->client->followRedirect();
        $this->postCapaWorkflow($email, $id, ['workflow_action' => 'validate']);
        $this->client->followRedirect();
        $this->postCapaWorkflow($email, $id, ['workflow_action' => 'start']);
        $this->client->followRedirect();
        $this->postCapaWorkflow($email, $id, ['workflow_action' => 'implementation_done']);
        $this->client->followRedirect();

        $this->postCapaWorkflow($email, $id, [
            'workflow_action' => 'close',
            'effectiveness_verification' => '',
            'effectiveness_comment' => '',
        ]);
        $this->entityManager->clear();
        $reloaded = $this->entityManager->find(CAPAAction::class, $id);
        self::assertInstanceOf(CAPAAction::class, $reloaded);
        self::assertSame(CapaStatus::EN_ATTENTE_DE_VERIFICATION, $reloaded->getStatus());

        $this->postCapaWorkflow($email, $id, [
            'workflow_action' => 'close',
            'effectiveness_verification' => 'Contrôle terrain conforme.',
            'effectiveness_comment' => 'OK',
        ]);
        $this->entityManager->clear();
        $final = $this->entityManager->find(CAPAAction::class, $id);
        self::assertInstanceOf(CAPAAction::class, $final);
        self::assertSame(CapaStatus::CLOTUREE, $final->getStatus());
        self::assertNotNull($final->getClosedAt());
    }
}
