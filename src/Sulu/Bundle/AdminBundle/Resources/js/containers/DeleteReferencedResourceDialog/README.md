The `DeleteReferencedResourceDialog` warns the user that a resource, which is about to be deleted, is still referenced
by other resources. The `referencingResourcesData` prop takes one object or an array of objects, each describing the
`resource`, its `referencingResources` and the `referencingResourcesCount`. Titles are truncated and, if a translation
for `sulu_reference.resource.<resourceKey>` exists, the type is appended.

If `allowDeletion` is `true` (default) the user can confirm the deletion or cancel. Otherwise only an acknowledging
button is shown and a click on it calls `onCancel`. `confirmLoading` shows a loader on the confirm button.

```javascript
const [open, setOpen] = React.useState(false);
const [allowDeletion, setAllowDeletion] = React.useState(true);

const referencingResourcesData = {
    resource: {id: 1, resourceKey: 'pages', title: 'Homepage'},
    referencingResourcesCount: 2,
    referencingResources: [
        {id: 'a', resourceKey: 'pages', title: 'About us'},
        {id: 'b', resourceKey: 'snippets', title: 'Footer'},
    ],
};

<div>
    <button onClick={() => { setAllowDeletion(true); setOpen(true); }}>Deletion allowed</button>
    <button onClick={() => { setAllowDeletion(false); setOpen(true); }}>Deletion forbidden</button>
    {open && (
        <DeleteReferencedResourceDialog
            allowDeletion={allowDeletion}
            confirmLoading={false}
            onCancel={() => setOpen(false)}
            onConfirm={() => setOpen(false)}
            referencingResourcesData={referencingResourcesData}
        />
    )}
</div>
```

Multiple resources can be passed as an array, e.g. when several items are deleted at once.

```javascript
const [open, setOpen] = React.useState(false);

const referencingResourcesData = [
    {
        resource: {id: 1, resourceKey: 'pages', title: 'Homepage'},
        referencingResourcesCount: 1,
        referencingResources: [{id: 'a', resourceKey: 'pages', title: 'About us'}],
    },
    {
        resource: {id: 2, resourceKey: 'pages', title: 'Contact'},
        referencingResourcesCount: 1,
        referencingResources: [{id: 'b', resourceKey: 'snippets', title: 'Footer'}],
    },
];

<div>
    <button onClick={() => setOpen(true)}>Open dialog</button>
    {open && (
        <DeleteReferencedResourceDialog
            confirmLoading={false}
            onCancel={() => setOpen(false)}
            onConfirm={() => setOpen(false)}
            referencingResourcesData={referencingResourcesData}
        />
    )}
</div>
```
