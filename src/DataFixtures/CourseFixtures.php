<?php

namespace App\DataFixtures;

use App\Entity\Course;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CourseFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $courses = [
            [
                'code' => 'PPBIB',
                'type' => 2,
                'price' => 2000,
                'title' => 'Программирование на Python (базовый)',
            ],
            [
                'code' => 'PPBI',
                'type' => 1,
                'price' => 2000,
                'title' => 'Программирование на Python (продвинутый)',
            ],
            [
                'code' => 'PPBI2',
                'type' => 3,
                'price' => 2000,
                'title' => 'Программирование на Python 2',
            ],
            [
                'code' => 'MSCB',
                'type' => 2,
                'price' => 1000,
                'title' => 'Математическая статистика (базовый)',
            ],
            [
                'code' => 'MSC',
                'type' => 3,
                'price' => 1000,
                'title' => 'Математическая статистика',
            ],
            [
                'code' => 'CAMPB',
                'type' => 2,
                'price' => 3000,
                'title' => 'Курс подготовки вожатых (базовый)',
            ],
            [
                'code' => 'CAMP',
                'type' => 1,
                'price' => 3000,
                'title' => 'Курс подготовки вожатых (продвинутый)',
            ],
        ];

        foreach ($courses as $course) {
            $newCourse = new Course();
            $newCourse->setCode($course['code']);
            $newCourse->setType($course['type']);
            if (isset($course['price'])) {
                $newCourse->setPrice($course['price']);
            }
            $newCourse->setTitle($course['title']);
            $manager->persist($newCourse);
        }
        $manager->flush();
    }
}