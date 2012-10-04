<?php

namespace Whiteboard;

class Whiteboard_IndexController extends Whiteboard_AbstractController {

    public function index_administrator() {
        $options = array(
            array(
                'id' => 'dojo_class',
                'text' => 'Dojo - widok zajęć',
                'image' => $this->baseLink . 'img/dojo_32x32.png',
                'href' => $this->baseUrl . '&c=lesson&b=dojo'
            ),
            array(
                'id' => 'faq',
                'text' => 'Pytania i odpowiedzi',
                'image' => $this->baseLink . 'img/question_32x32.png',
                'href' => $this->baseUrl . '&c=faq&b=index'
            ),
            array(
                'id' => 'group',
                'text' => 'Zarządzanie grupami',
                'image' => $this->baseLink . 'img/class_32x32.png',
                'href' => $this->baseUrl . '&c=group&a=config'
            ),
            array(
                'id' => 'config',
                'text' => 'Zarządzanie uprawnieniami',
                'image' => '32x32/users.png',
                'href' => $this->baseUrl . '&c=config&a=powers'
            ),
            array(
                'id' => 'union_admin',
                'text' => 'Administracja serwera Union',
                'image' => '32x32/generic.png',
                'href' => $this->baseLink . 'other/UnionAdmin.swf'
            )
        );

        $this->assign('OPTIONS', $options);
    }
}

