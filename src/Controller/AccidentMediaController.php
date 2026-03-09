<?php

namespace App\Controller;

use App\Entity\Accident;
use App\Entity\AccidentMedia;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/accidents')]
#[IsGranted('IS_AUTHENTICATED_FULLY')]
class AccidentMediaController extends AbstractController
{
    #[Route('/{id}/medias', name: 'app_accident_media_add', methods: ['POST'])]
    public function add(Accident $accident, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('edit', $accident);

        $csrfToken = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('accident_media_add' . $accident->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $type = (string) $request->request->get('type', AccidentMedia::TYPE_PHOTO);
        if (!in_array($type, [AccidentMedia::TYPE_PHOTO, AccidentMedia::TYPE_DOCUMENT, AccidentMedia::TYPE_CROQUIS], true)) {
            $type = AccidentMedia::TYPE_DOCUMENT;
        }

        /** @var UploadedFile|null $file */
        $file = $request->files->get('file');
        if (!$file instanceof UploadedFile) {
            $this->addFlash('error', 'Aucun fichier sélectionné.');
            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_dashboard'));
        }

        $uploadRoot = $this->getParameter('accident_upload_dir');
        if (!is_string($uploadRoot) || $uploadRoot === '') {
            throw new \RuntimeException('Paramètre accident_upload_dir manquant.');
        }

        $extension = $file->guessExtension() ?: $file->getClientOriginalExtension();
        $safeExt = $extension ? ('.' . preg_replace('/[^a-zA-Z0-9]/', '', $extension)) : '';
        $fileName = sprintf('acc_%d_%s%s', $accident->getId(), bin2hex(random_bytes(8)), $safeExt);

        if (!is_dir($uploadRoot)) {
            @mkdir($uploadRoot, 0775, true);
        }

        $file->move($uploadRoot, $fileName);

        $media = new AccidentMedia();
        $media->setAccident($accident);
        $media->setCreatedBy($this->getUser());
        $media->setType($type);
        $media->setOriginalName($file->getClientOriginalName() ?: $fileName);
        $media->setFileName($fileName);
        $media->setMimeType($file->getClientMimeType());
        $media->setSize($file->getSize());

        $entityManager->persist($media);
        $entityManager->flush();

        $this->addFlash('success', 'Fichier ajouté avec succès.');
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_dashboard'));
    }

    #[Route('/medias/{id}/delete', name: 'app_accident_media_delete', methods: ['POST'])]
    public function delete(AccidentMedia $media, Request $request, EntityManagerInterface $entityManager): Response
    {
        $this->denyAccessUnlessGranted('delete', $media);

        $csrfToken = (string) $request->request->get('_token');
        if (!$this->isCsrfTokenValid('accident_media_delete' . $media->getId(), $csrfToken)) {
            throw $this->createAccessDeniedException('Token CSRF invalide.');
        }

        $uploadRoot = $this->getParameter('accident_upload_dir');
        if (is_string($uploadRoot) && $uploadRoot !== '') {
            $fullPath = rtrim($uploadRoot, '\\/') . DIRECTORY_SEPARATOR . $media->getFileName();
            if (is_file($fullPath)) {
                @unlink($fullPath);
            }
        }

        $entityManager->remove($media);
        $entityManager->flush();

        $this->addFlash('success', 'Fichier supprimé avec succès.');
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('app_dashboard'));
    }
}
