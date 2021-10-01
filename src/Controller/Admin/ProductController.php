<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Product;
use App\Form\Type\ProductType;
use Doctrine\ORM\EntityManager;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class ProductController extends AbstractController
{
  /**  */
  private $uploadableManager;

  public function __construct(UploadableManager $uploadableManager)
  {
    $this->uploadableManager = $uploadableManager;
  }

  public function index(Request $request): Response
  {
    $category = null;
    $categoryId = $request->query->get('categoryId');
    if ($categoryId) $category = $this->getDoctrine()->getRepository(Category::class)->findOneBy(['id' => $categoryId]);
    $categories = $this->getDoctrine()->getRepository(Category::class)->findBy([], ['position' => 'ASC']);
    $products = $this->getDoctrine()->getRepository(Product::class)->findByCategory($category);

//        var_dump($products, $categoryId );
    return $this->render('admin/product/index.html.twig', [
        'categories' => $categories,
        'products' => $products,
        'category' => $category,
    ]);
  }

  public function edit(Request $request, $id)
  {

    if ($id) {
      $product = $this->getDoctrine()->getRepository(Product::class)->find($id);
      if (!$product) {
        $this->addFlash('error', 'Произошла ошибка при редактировании товара, попробуйте еще раз');
        return $this->redirectToRoute('admin_products');
      }
    } else {
      $product = new Product();
      $product->setEnabled(true);
      $result = $this->getDoctrine()->getRepository(Product::class)->findBy([], ['position' => 'DESC'], 1 );
      $product->setPosition($result && isset($result[0]) ? $result[0]->getPosition() + 1 : 0);
    }

    $form = $this->createForm(ProductType::class, $product);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
      $em = $this->getDoctrine()->getManager();

      if ($product->getImage() && $product->getImage()->getFile()) {
        $product->getImage()->setUploadPath('media/product_image');
        $this->uploadableManager->markEntityToUpload($product->getImage(), $product->getImage()->getFile());
      }

      if (!$product->getId()) {
        $em->persist($product);
      }

      $em->flush();

      return new Response($this->generateUrl('admin_products', [], UrlGeneratorInterface::ABSOLUTE_URL));
    }
    // die();
    return $this->render('admin\product\edit.html.twig', [
        'form' => $form->createView()
    ]);
  }

  private function getEntityManager(): EntityManager
  {
      return $this->getDoctrine()->getManager();
  }

  public function changeStatus(Request $request): Response {
    $ids = (array) $request->request->get('ids');
    $enabled = filter_var($request->request->get('enabled'), FILTER_VALIDATE_BOOLEAN);
    if (!$ids || !count($ids) || $enabled === null || $enabled === '') {
      return new Response('Не указан один из обязательных параметров.', 404);
    }
    $em = $this->getEntityManager();
    $batchSize = 20;
    $i = 1;
    $q = $em->createQuery('select p from App\Entity\Product p WHERE p.id IN (:ids)')->setParameter('ids', $ids);
    foreach ($q->toIterable() as $product) {
        $product->setEnabled($enabled);
        ++$i;
        if (($i % $batchSize) === 0) {
            $em->flush();
            $em->clear();
        }
    }
    $em->flush();
    return new Response('Success', 200);
  }

  public function delete($id)
  {
    $product = $this->getDoctrine()->getRepository(Product::class)->find($id);
    $em = $this->getDoctrine()->getManager();

    $em->remove($product);
    $em->flush();

    return $this->redirect($this->generateUrl('admin_products'));
  }

  public function positions(Request $request)
  {
      /** @var Array|null */
      $positions = $request->request->get('positions');

      $categoryId = $request->get('categoryId');

      if ($positions) {
        $ids = array_values($positions);
        $products = $this->getDoctrine()->getRepository(Product::class)->findBy(['id' => $ids], ['position' => 'ASC']);
        $oldPositions = [];
        foreach ($products as $product) {
          array_push($oldPositions, $product->getPosition());
        }
        // $this->getDoctrine()->getRepository(Product::class)->updatePositions($positions, $oldPositions, $categoryId);
        $flippedPositions = array_flip($positions);
        $batchSize = 20;
        $i = 1;
        $em = $this->getDoctrine()->getManager();
        foreach ($products as $product) {
            $positionIdx = $flippedPositions[$product->getId()];
            $product->setPosition($oldPositions[$positionIdx]);
            ++$i;
            if (($i % $batchSize) === 0) {
                $em->flush(); // Executes all updates.
                $em->clear(); // Detaches all objects from Doctrine!
            }
        }
        $em->flush();
      }

      return new JsonResponse(['success' => true, 'data' => $positions]);
  }
}
