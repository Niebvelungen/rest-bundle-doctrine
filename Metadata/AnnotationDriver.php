<?php
namespace Dontdrinkandroot\RestBundle\Metadata;

use Doctrine\Common\Annotations\Reader;
use Doctrine\ORM\EntityManagerInterface;
use Dontdrinkandroot\RestBundle\Metadata\Annotation\Excluded;
use Dontdrinkandroot\RestBundle\Metadata\Annotation\Includable;
use Dontdrinkandroot\RestBundle\Metadata\Annotation\Postable;
use Dontdrinkandroot\RestBundle\Metadata\Annotation\Puttable;
use Dontdrinkandroot\RestBundle\Metadata\Annotation\RootResource;
use Dontdrinkandroot\RestBundle\Metadata\Annotation\SubResource;
use Metadata\Driver\DriverInterface;

class AnnotationDriver implements DriverInterface
{
    private $reader;

    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    public function __construct(Reader $reader, EntityManagerInterface $entityManager)
    {
        $this->reader = $reader;
        $this->entityManager = $entityManager;
    }

    /**
     * {@inheritdoc}
     */
    public function loadMetadataForClass(\ReflectionClass $class)
    {
        $doctrineClassMetadata = $this->entityManager->getClassMetadata($class->getName());
        $ddrRestClassMetadata = new ClassMetadata($class->getName());

        /** @var RootResource $restResourceAnnotation */
        $restResourceAnnotation = $this->reader->getClassAnnotation($class, RootResource::class);
        if (null !== $restResourceAnnotation) {

            $ddrRestClassMetadata->setRestResource(true);

            if (null !== $restResourceAnnotation->namePrefix) {
                $ddrRestClassMetadata->setNamePrefix($restResourceAnnotation->namePrefix);
            }

            if (null !== $restResourceAnnotation->pathPrefix) {
                $ddrRestClassMetadata->setPathPrefix($restResourceAnnotation->pathPrefix);
            }

            if (null !== $restResourceAnnotation->service) {
                $ddrRestClassMetadata->setService($restResourceAnnotation->service);
            }

            if (null !== $restResourceAnnotation->controller) {
                $ddrRestClassMetadata->setController($restResourceAnnotation->controller);
            }

            if (null !== $restResourceAnnotation->listRight) {
                $ddrRestClassMetadata->setListRight($restResourceAnnotation->listRight);
            }

            if (null !== $restResourceAnnotation->postRight) {
                $ddrRestClassMetadata->setPostRight($restResourceAnnotation->postRight);
            }

            if (null !== $restResourceAnnotation->getRight) {
                $ddrRestClassMetadata->setGetRight($restResourceAnnotation->getRight);
            }

            if (null !== $restResourceAnnotation->putRight) {
                $ddrRestClassMetadata->setPutRight($restResourceAnnotation->putRight);
            }

            if (null !== $restResourceAnnotation->deleteRight) {
                $ddrRestClassMetadata->setDeleteRight($restResourceAnnotation->deleteRight);
            }

            if (null !== $restResourceAnnotation->methods) {
                $ddrRestClassMetadata->setMethods($restResourceAnnotation->methods);
            }
        }

        foreach ($class->getProperties() as $reflectionProperty) {

            $propertyMetadata = new PropertyMetadata($class->getName(), $reflectionProperty->getName());

            $puttableAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, Puttable::class);
            if (null !== $puttableAnnotation) {
                $propertyMetadata->setPuttable(true);
            }

            $postableAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, Postable::class);
            if (null !== $postableAnnotation) {
                $propertyMetadata->setPostable(true);
            }

            $includableAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, Includable::class);
            if (null !== $includableAnnotation) {
                $propertyMetadata->setIncludable(true);
            }

            $excludedAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, Excluded::class);
            if (null !== $excludedAnnotation || $doctrineClassMetadata->hasAssociation($propertyMetadata->name)) {
                $propertyMetadata->setExcluded(true);
            }

            /** @var SubResource $subResourceAnnotation */
            $subResourceAnnotation = $this->reader->getPropertyAnnotation($reflectionProperty, SubResource::class);
            if (null !== $subResourceAnnotation) {

                $propertyMetadata->setSubResource(true);
                if (null !== $subResourceAnnotation->listRight) {
                    $propertyMetadata->setSubResourceListRight($subResourceAnnotation->listRight);
                }

                if (null !== $subResourceAnnotation->path) {
                    $propertyMetadata->setSubResourcePath($subResourceAnnotation->path);
                }

                if (null !== $subResourceAnnotation->postRight && null === $subResourceAnnotation->entityClass) {
                    throw new \RuntimeException('Must provide entity class for postable sub resource');
                }

                if (null !== $subResourceAnnotation->postRight) {
                    $propertyMetadata->setSubResourcePostRight($subResourceAnnotation->postRight);
                }

                if (null !== $subResourceAnnotation->entityClass) {
                    $propertyMetadata->setSubResourceEntityClass($subResourceAnnotation->entityClass);
                }
            }

            $ddrRestClassMetadata->addPropertyMetadata($propertyMetadata);
        }

        return $ddrRestClassMetadata;
    }
}
