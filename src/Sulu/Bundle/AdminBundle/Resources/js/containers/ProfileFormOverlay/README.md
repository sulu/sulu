The `ProfileFormOverlay` shows a [`FormOverlay`](#formoverlay) in which the logged in user edits their profile. The
form with the key `profile_details` and the data of the `profile` resource are loaded from the server, and a new store is
created every time the overlay is reopened. After saving, the user store is updated. As it needs a running admin
application, the example below is not executed.

| Prop      | Description                                                    |
|-----------|----------------------------------------------------------------|
| `open`    | Whether the overlay is visible.                                |
| `onClose` | Called when the overlay is closed, either by cancel or save.   |

```javascript static
const [open, setOpen] = React.useState(false);

<div>
    <button onClick={() => setOpen(true)}>Edit profile</button>
    <ProfileFormOverlay onClose={() => setOpen(false)} open={open} />
</div>
```
