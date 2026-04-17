<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\CategoryType;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[Route('/admin/categories')]
#[IsGranted('ROLE_ADMIN')]
class CategoryAdminController extends AbstractController
{
    #[Route('', name: 'app_admin_category_index')]
    public function index(CategoryRepository $categoryRepository): Response
    {
        return $this->render('admin/category/index.html.twig', [
            'categories' => $categoryRepository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/nouvelle', name: 'app_admin_category_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category, [
            'current_category' => $category,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ('root' === $form->get('hierarchyType')->getData()) {
                $category->setParent(null);
            }

            $this->handleImageUpload($form->get('imageFile')->getData(), $category, $slugger);
            $entityManager->persist($category);
            $entityManager->flush();
            $this->addFlash('success', 'Categorie creee avec succes.');

            return $this->redirectToRoute('app_admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'mode' => 'Creer',
        ]);
    }

    #[Route('/{id}/modifier', name: 'app_admin_category_edit', requirements: ['id' => '\d+'])]
    public function edit(Category $category, Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(CategoryType::class, $category, [
            'current_category' => $category,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ('root' === $form->get('hierarchyType')->getData()) {
                $category->setParent(null);
            }

            if ($category->getParent() instanceof Category && $category->getParent()->getId() === $category->getId()) {
                $this->addFlash('error', 'Une categorie ne peut pas etre sa propre parente.');

                return $this->redirectToRoute('app_admin_category_edit', ['id' => $category->getId()]);
            }

            $this->handleImageUpload($form->get('imageFile')->getData(), $category, $slugger);
            $entityManager->flush();
            $this->addFlash('success', 'Categorie mise a jour.');

            return $this->redirectToRoute('app_admin_category_index');
        }

        return $this->render('admin/category/form.html.twig', [
            'form' => $form,
            'mode' => 'Modifier',
        ]);
    }

    #[Route('/{id}/supprimer', name: 'app_admin_category_delete', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function delete(Category $category, Request $request, EntityManagerInterface $entityManager): Response
    {
        if (!$this->isCsrfTokenValid('delete_category_'.$category->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('error', 'Jeton de securite invalide.');

            return $this->redirectToRoute('app_admin_category_index');
        }

        if ($category->getPosts()->count() > 0) {
            $this->addFlash('error', 'Impossible de supprimer une categorie qui contient des articles.');

            return $this->redirectToRoute('app_admin_category_index');
        }

        $entityManager->remove($category);
        $entityManager->flush();
        $this->addFlash('success', 'Categorie supprimee.');

        return $this->redirectToRoute('app_admin_category_index');
    }

    private function handleImageUpload(?UploadedFile $imageFile, Category $category, SluggerInterface $slugger): void
    {
        if (!$imageFile instanceof UploadedFile) {
            return;
        }

        $uploadDir = $this->getParameter('kernel.project_dir').'/public/uploads/categories';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = (string) $slugger->slug($originalFilename);
        if ('' === $safeFilename) {
            $safeFilename = 'category-image';
        }
        if (strlen($safeFilename) > 80) {
            $safeFilename = substr($safeFilename, 0, 80);
        }

        $extension = $imageFile->guessExtension();
        $clientExtension = strtolower((string) $imageFile->getClientOriginalExtension());
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        if (!is_string($extension) || '' === $extension || !in_array(strtolower($extension), $allowedExtensions, true)) {
            $extension = in_array($clientExtension, $allowedExtensions, true) ? $clientExtension : 'png';
        }

        $newFilename = $safeFilename.'-'.uniqid().'.'.$extension;

        try {
            $imageFile->move($uploadDir, $newFilename);
        } catch (FileException) {
            $this->addFlash('error', 'Impossible de televerser cette image de categorie.');

            return;
        }

        $category->setPicture('/uploads/categories/'.$newFilename);
    }
}
