The `Link` component is a universal link selection and can additionally also be used for external links.
The component displays a `Select` together with an input field. The `select` component is used to choose the type
(page, media, external, article). The input field renders the currently selected entity or the external url.
Internally the `Link` uses a specific overlay depending on the selected type. E.g. medias use the `MediaLinkTypeOverlay`.  

As soon as a new type is selected, the type specific overlay is opened and the user can select the entity or insert 
the external url. Furthermore, it is configurable if setting an `anchor` or a `target` for the resulting url, 
is allowed in the overlay.

The types can be extended by adding new entries to the `linkTypeRegistry`.
```javascript
    import linkTypeRegistry from 'sulu-admin-bundle/containers/Link/registries/linkTypeRegistry';
    import {MediaLinkTypeOverlay} from 'sulu-media-bundle/containers/Link';

    linkTypeRegistry.add('media', MediaLinkTypeOverlay, translate('sulu_media.media'));
```

```javascript
<LinkContainer
    disabled={false}
    enableAnchor={true}
    enableTarget={true}
    locale={locale}
    onChange={onChange}
    onFinish={onFinish}
    types={['page', 'media', 'external']}
    value={undefined}
/>
```
