<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Form\Type\CategoryType;
use App\Service\TelegramService;
use Doctrine\ORM\EntityManager;
use Stof\DoctrineExtensionsBundle\Uploadable\UploadableManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class CategoryController extends AbstractController
{
    /**  */
    private $uploadableManager;

    public function __construct(UploadableManager $uploadableManager, TelegramService $telegamService)
    {
        $this->uploadableManager = $uploadableManager;
        $this->telegamService = $telegamService;
    }
    
    public function index(): Response
    {
        
      $categories = $this->getDoctrine()->getRepository(Category::class)->findBy([], ['deletedAt' => 'ASC', 'position'=>'ASC' ]);

      return $this->render('admin/category/index.html.twig', [
          'categories' => $categories,
      ]);
    }

    public function create(Request $request): Response
    {
        $category = new Category();
        $category->setEnabled(true);
        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $result = $this->getDoctrine()->getRepository(Category::class)->findBy([], ['position'=>'DESC'], 1);
            if (!$category->getPosition()) {
                if (count($result) && $result[0]->getPosition()) {
                    $category->setPosition($result[0]->getPosition() + 1);
                } else {
                    $category->setPosition(1);
                }
            }

            if ($category->getImage() && $category->getImage()->getFile()) {
                $category->getImage()->setUploadPath('media/category_image');
                $this->uploadableManager->markEntityToUpload($category->getImage(), $category->getImage()->getFile());
            }

            if (!$category->getId()) {
                $em->persist($category);
            }

            $em->flush();

            return $this->redirect($this->generateUrl('admin_categories'));
        }
        
        return $this->render('admin\category\edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function update(Request $request, $id): Response
    {
        $category = $this->getDoctrine()->getRepository(Category::class)->find($id);
        if (!$category) {
            return $this->redirectToRoute('admin_categories');
        }

        $form = $this->createForm(CategoryType::class, $category);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();

            if ($category->getImage() && $category->getImage()->getFile()) {
                $category->getImage()->setUploadPath('media/category_image');
                $this->uploadableManager->markEntityToUpload($category->getImage(), $category->getImage()->getFile());
            }

            $em->flush();

            return $this->redirect($this->generateUrl('admin_categories'));
        }

        return $this->render('admin\category\edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function delete($id)
    {
        $category = $this->getDoctrine()->getRepository(Category::class)->find($id);
        // TODO: пересчёт номеров позиций
        $em = $this->getDoctrine()->getManager();

        $em->remove($category);
        $em->flush();

        return $this->redirect($this->generateUrl('admin_categories'));
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
        $q = $em->createQuery('select c from App\Entity\Category c WHERE c.id IN (:ids)')->setParameter('ids', $ids);
        foreach ($q->toIterable() as $category) {
            $category->setEnabled($enabled);
            ++$i;
            if (($i % $batchSize) === 0) {
                $em->flush();
                $em->clear();
            }
        }
        $em->flush();
        return new Response('Success', 200);
      }

    public function positions(Request $request)
    {
        $positions = $request->request->get('positions');
        if ($positions) {
            $this->getDoctrine()->getRepository(Category::class)->updatePositions($positions);
        }

        return new JsonResponse(['success' => true, 'data' => $positions]);
    }
}
