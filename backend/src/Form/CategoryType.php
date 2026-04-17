<?php

namespace App\Form;

use App\Entity\Category;
use App\Repository\CategoryRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        /** @var Category|null $currentCategory */
        $currentCategory = $options['current_category'];
        $isSubCategory = $currentCategory instanceof Category && $currentCategory->getParent() instanceof Category;

        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom de la categorie',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
            ])
            ->add('hierarchyType', ChoiceType::class, [
                'label' => 'Type',
                'mapped' => false,
                'choices' => [
                    'Categorie' => 'root',
                    'Sous-categorie' => 'sub',
                ],
                'data' => $isSubCategory ? 'sub' : 'root',
            ])
            ->add('parent', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'label' => 'Categorie parente',
                'required' => false,
                'placeholder' => 'Aucune (categorie racine)',
                'query_builder' => static function (CategoryRepository $categoryRepository) use ($currentCategory) {
                    $qb = $categoryRepository->createQueryBuilder('c')
                        ->andWhere('c.parent IS NULL')
                        ->orderBy('c.name', 'ASC');

                    if ($currentCategory instanceof Category && null !== $currentCategory->getId()) {
                        $qb->andWhere('c.id != :currentId')
                            ->setParameter('currentId', $currentCategory->getId());
                    }

                    return $qb;
                },
            ])
            ->add('imageFile', FileType::class, [
                'label' => 'Image de la categorie',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'accept' => '.jpg,.jpeg,.png,.webp',
                ],
                'constraints' => [
                    new File(
                        maxSize: '4M',
                        extensions: ['jpg', 'jpeg', 'png', 'webp'],
                        extensionsMessage: 'Merci de televerser une image JPG, PNG ou WEBP.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
            'current_category' => null,
        ]);
    }
}
