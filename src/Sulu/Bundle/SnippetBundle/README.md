SuluSnippetBundle
=================

[![Build Status](https://travis-ci.org/sulu-cmf/SuluSnippetBundle.svg?branch=refactor_for_preview)](https://travis-ci.org/sulu-cmf/SuluSnippetBundle)

## Usage

Snippets can be managed using existing APIs.

Why not use a SnippetManager to act as a proxy?

- More code to manager = more errors
- Limits reuse of existing code (e.g. AJAX endpoints)

Get snippet types:

    $structureManager = $this->getContainer('sulu.structure_manager');
    $snippetTypes = $structureManager->getTemplates(Structure::TYPE_SNIPPET);

Get a snippet:

    $contentMapper = $this->getContainer('sulu.content.mapper');
    $contentMapper->load($snippetUuid, $webspace, $languageCode);

Save a snippet:
    
    $req = ContentMapperRequest::create()
        ->setType('snippet')
        ->setTemplateKey('hotel')
        ->setLocale('de')
        ->setUserId(1)
        ->setData(array(
            'name' => 'L\'Hôtel New Hampshire',
        ));
    $hotel2 = $this->contentMapper->saveRequest($req);
    $contentMapper->saveRequest(

**WHY?**:

- Dependency management: A manager which does all of the above has overlapping
  dependencies and lack of purpose
- Refactoring: Using proxies protects against refactoring breakages in one
  respect but makes things worse ultimately -- you end up having lots of
  different interfaces for the same things.

## TODO

- API Security
- Template loaders (in sulu/sulu!)
