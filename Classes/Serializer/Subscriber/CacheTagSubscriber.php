<?php

namespace Xima\T3ApiCache\Serializer\Subscriber;

use JMS\Serializer\EventDispatcher\Events;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use SourceBroker\T3api\Domain\Repository\ApiResourceRepository;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\DomainObject\AbstractDomainObject;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;

readonly class CacheTagSubscriber implements EventSubscriberInterface
{
    public function __construct(protected ApiResourceRepository $apiResourceRepository, private readonly Context $context)
    {
    }

    /**
     * @return array<int, array<string, string>>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            [
                'event' => Events::POST_SERIALIZE,
                'method' => 'onPostSerialize',
            ],
        ];
    }

    public function onPostSerialize(ObjectEvent $event): void
    {
        $activeApiCache = $this->context->hasAspect('t3api_cache') && $this->context->getPropertyFromAspect('t3api_cache', 'isActive');
        if (!$activeApiCache || !$event->getObject() instanceof AbstractDomainObject) {
            return;
        }

        $type = $event->getType();

        if (class_exists($type['name'])) {
            $entity = $event->getObject();
            /** @var JsonSerializationVisitor $visitor */
            $visitor = $event->getVisitor();
            $dataMapper = GeneralUtility::makeInstance(DataMapper::class);
            $tableName = $dataMapper->getDataMap($type['name'])->getTableName();
            $cacheTag = $tableName . '_' . $entity->getUid();
            $visitor->visitProperty(
                new StaticPropertyMetadata(AbstractDomainObject::class, '@cache_tag', $cacheTag),
                $cacheTag
            );
        }
    }
}
