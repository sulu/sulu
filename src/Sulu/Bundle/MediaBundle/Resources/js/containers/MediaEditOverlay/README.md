The `MediaEditOverlay` allows to edit the details of a single media (title, description, copyright, taxonomies, ...)
without leaving the current view. It loads the media with the given `id` in the given `locale` and renders the
`media_details` form, which is the same form used in the "Information and Taxonomy" tab of the media detail view.

The changes are saved when the confirm button is clicked, after that the `onConfirm` callback is called. This is used
by the `SingleMediaSelection` and `MultiMediaSelection` containers to edit a selected media directly from a form.

```javascript static
import {observable} from 'mobx';
import {MediaEditOverlay} from 'sulu-media-bundle/containers';

<MediaEditOverlay
    id={5}
    locale={observable.box('en')}
    onClose={() => {}}
    onConfirm={() => {}}
    open={true}
/>
```
