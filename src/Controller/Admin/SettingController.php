<?php
namespace App\Controller\Admin;

use App\Entity\Setting;
use App\Form\Type\SettingType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

class SettingController extends AbstractController
{

    public function index()
    {
        $settings = $this->getDoctrine()->getRepository(Setting::class)->findAll();

        return $this->render('admin\setting\index.html.twig', [
            'settings' => $settings
        ]);
    }

    public function editSetting(Request $request, ?int $id)
    {
        if (!$id) {
            $setting = new Setting();
        } else {
            $setting = $this->getRepo()->find($id);
        }


        $form = $this->createForm(SettingType::class, $setting);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            if (!$setting->getId()) {
                $em->persist($setting);
            }

            $em->flush();

            return $this->redirect($this->generateUrl('admin_settings'));
        }

        return $this->render('admin\setting\edit.html.twig', [
            'form' => $form->createView()
        ]);
    }

    public function removeSetting($id)
    {
        $setting = $this->getRepo()->find($id);
        $em = $this->getDoctrine()->getManager();

        $em->remove($setting);
        $em->flush();

        return $this->redirect($this->generateUrl('admin_settings'));
    }

    private function getRepo()
    {
        return $this->getDoctrine()->getRepository(Setting::class);
    }
}
