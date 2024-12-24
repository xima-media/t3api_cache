<?php

namespace Xima\T3ApiCache\Annotation;

enum CacheStrategy: string
{
    case NO_TAGS = 'noTags';
    case SINGLE = 'singleTag';
    case MULTIPLE = 'multipleTags';
    case DEEP = 'multipleTagsDeep';
}
