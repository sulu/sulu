The `DeleteDependantResourcesDialog` deletes a list of dependant resources in batches and shows the progress. It is
used when deleting an item requires deleting other items first. The batches are processed one after another, the
resources inside of a batch in parallel. Each resource is deleted with the [`ResourceRequester`](#resourcerequester),
therefore the example below is not executed.

| Prop                     | Description                                                                              |
|--------------------------|------------------------------------------------------------------------------------------|
| `dependantResourcesData` | `title`, `detail`, `dependantResourcesCount` and `dependantResourceBatches` (array of arrays of `{id, resourceKey}`). |
| `onCancel`               | Called when the dialog is closed, a running deletion is aborted.                         |
| `onError`                | Called with the error response body if a deletion failed.                                |
| `onFinish`               | Called after all batches have been deleted.                                              |
| `requestOptions`         | Additional parameters sent with each delete request. Changing them resets the dialog.    |

If a resource is still referenced (HTTP 409 with the referencing-resources error code) the dialog lists the
referencing resources and asks for confirmation. After the confirmation the resource is deleted with `force: true`.

```javascript static
<DeleteDependantResourcesDialog
    dependantResourcesData={{
        title: 'Delete category and sub categories',
        detail: 'The category has 3 sub categories, which will be deleted too.',
        dependantResourcesCount: 3,
        dependantResourceBatches: [
            [{id: 3, resourceKey: 'categories'}, {id: 4, resourceKey: 'categories'}],
            [{id: 5, resourceKey: 'categories'}],
        ],
    }}
    onCancel={handleCancel}
    onError={handleError}
    onFinish={handleFinish}
    requestOptions={{locale: 'en'}}
/>
```
