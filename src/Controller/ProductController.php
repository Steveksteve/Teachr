<?php

namespace App\Controller;

use App\Entity\Product;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ProductController extends AbstractController
{
    #[Route('/api/products', name: 'create_product', methods: ['POST'])]
    public function createProduct(
        Request $request,
        EntityManagerInterface $entityManager,
        CategoryRepository $categoryRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Validation des données
        if (
            empty($data['name']) ||
            empty($data['description']) ||
            empty($data['price']) ||
            empty($data['createdAt']) ||
            empty($data['categoryId'])
        ) {
            return new JsonResponse([
                'error' => 'Tous les champs (name, description, price, createdAt, categoryId) sont requis'
            ], JsonResponse::HTTP_BAD_REQUEST);
        }

        // Vérification de la catégorie
        $category = $categoryRepository->find($data['categoryId']);
        if (!$category) {
            return new JsonResponse(['error' => 'Catégorie non trouvée'], JsonResponse::HTTP_BAD_REQUEST);
        }

        try {
            // Créer et enregistrer le produit
            $product = new Product();
            $product->setName($data['name']);
            $product->setDescription($data['description']);
            $product->setPrice((float) $data['price']);
            $product->setCreatedAt(new \DateTime($data['createdAt']));
            $product->setCategory($category);

            // Sauvegarder le produit
            $entityManager->persist($product);
            $entityManager->flush();

            return new JsonResponse(['success' => 'Produit créé avec succès'], JsonResponse::HTTP_CREATED);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => $e->getMessage()], JsonResponse::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    #[Route('/api/products', name: 'get_products', methods: ['GET'])]
    public function getProducts(EntityManagerInterface $entityManager): JsonResponse
    {
        $repository = $entityManager->getRepository(Product::class);
        $products = $repository->findAll();
        $data = [];

        foreach ($products as $product) {
            $data[] = [
                'id' => $product->getId(),
                'name' => $product->getName(),
                'description' => $product->getDescription(),
                'price' => $product->getPrice(),
                'createdAt' => $product->getCreatedAt()->format('Y-m-d H:i:s'),
                'category' => $product->getCategory()->getName()
            ];
        }

        return new JsonResponse($data, JsonResponse::HTTP_OK);
    }
}
