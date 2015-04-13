<?php

namespace Sulu\Component\Content\Document;

/**
 * Interface to be implemented by documents which contain content
 */
interface ContentDocumentInterface extends 
    TimestampBehavior,
    BlameBehavior,
    ContentBehavior,
    UuidBehavior,
    LocaleBehavior,
    ChildrenBehavior
{
}
