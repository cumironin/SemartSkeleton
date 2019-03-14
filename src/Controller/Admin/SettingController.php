<?php

declare(strict_types=1);

namespace KejawenLab\Semart\Skeleton\Controller\Admin;

use KejawenLab\Semart\Skeleton\Entity\Setting;
use KejawenLab\Semart\Skeleton\Pagination\Paginator;
use KejawenLab\Semart\Skeleton\Repository\SettingRepository;
use KejawenLab\Semart\Skeleton\Request\RequestHandler;
use KejawenLab\Semart\Skeleton\Security\Authorization\Permission;
use KejawenLab\Semart\Skeleton\Security\Service\SuperAdministratorCheckerService;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\SerializerInterface;

/**
 * @Route("/settings")
 *
 * @Permission(menu="SETTING")
 *
 * @author Muhamad Surya Iksanudin <surya.iksanudin@gmail.com>
 */
class SettingController extends AdminController
{
    /**
     * @Route("/", methods={"GET"}, name="settings_index", options={"expose"=true})
     *
     * @Permission(actions=Permission::VIEW)
     */
    public function index(Request $request, Paginator $paginator)
    {
        $settings = $paginator->paginate(Setting::class, (int) $request->query->get('page', 1));

        if ($request->isXmlHttpRequest()) {
            $table = $this->renderView('setting/table-content.html.twig', ['settings' => $settings]);
            $pagination = $this->renderView('setting/pagination.html.twig', ['settings' => $settings]);

            return new JsonResponse([
                'table' => $table,
                'pagination' => $pagination,
            ]);
        }

        return $this->render('setting/index.html.twig', ['title' => 'Setting', 'settings' => $settings]);
    }

    /**
     * @Route("/{id}", methods={"GET"}, name="settings_detail", options={"expose"=true})
     *
     * @Permission(actions=Permission::VIEW)
     */
    public function find(string $id, SettingRepository $repository, SerializerInterface $serializer)
    {
        $setting = $repository->find($id);
        if (!$setting) {
            throw new NotFoundHttpException();
        }

        return new JsonResponse($serializer->serialize($setting, 'json', ['groups' => ['read']]));
    }

    /**
     * @Route("/save", methods={"POST"}, name="settings_save", options={"expose"=true})
     *
     * @Permission(actions={Permission::ADD, Permission::EDIT})
     */
    public function save(Request $request, SettingRepository $repository, RequestHandler $requestHandler)
    {
        $primary = $request->get('id');
        if ($primary) {
            $setting = $repository->find($primary);
        } else {
            $setting = new Setting();
        }

        $requestHandler->handle($request, $setting);
        if (!$requestHandler->isValid()) {
            return new JsonResponse(['status' => 'KO', 'errors' => $requestHandler->getErrors()]);
        }

        $this->commit($setting);

        return new JsonResponse(['status' => 'OK']);
    }

    /**
     * @Route("/{id}/delete", methods={"POST"}, name="settings_remove", options={"expose"=true})
     *
     * @Permission(actions=Permission::DELETE)
     */
    public function delete(string $id, SettingRepository $repository)
    {
        if (!$setting = $repository->find($id)) {
            throw new NotFoundHttpException();
        }

        $this->remove($setting);

        return new JsonResponse(['status' => 'OK']);
    }

    /**
     * @Route("/{id}/restore", methods={"POST"}, name="settings_restore", options={"expose"=true})
     *
     * @Permission(actions=Permission::DELETE)
     */
    public function restore(string $id, SettingRepository $repository, SuperAdministratorCheckerService $service)
    {
        if (!$city = $repository->find($id) && $service->isAdmin()) {
            throw new NotFoundHttpException();
        }

        $repository->restore($id);

        return new JsonResponse(['status' => 'OK']);
    }
}