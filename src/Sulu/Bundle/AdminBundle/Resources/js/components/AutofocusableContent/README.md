# AutofocusableContent

`AutofocusableContent` is a wrapper component that renders an `<article>` and automatically focuses the first focusable form element (input, select, textarea, button, or element with a non-negative `tabindex`) when it mounts.

## When to use it

By moving focus to the first focusable element as soon as the content is shown, users can start typing or navigating the form immediately. This component is used inside [Overlay](../Overlay/README.md) and [Dialog](../Dialog/README.md) so that every form opened in a popup gets this behavior without each caller having to implement it.

## How to use it

Wrap the content (typically a form) with `AutofocusableContent` and pass an optional `className` for styling. Focus runs after the next paint so it works correctly with open/close transitions.

```javascript
import AutofocusableContent from '../AutofocusableContent';

<AutofocusableContent className={styles.article}>
    <Form store={formStore} onSubmit={handleSubmit} />
</AutofocusableContent>
```

### Props

| Prop        | Type   | Default | Description                                |
| ----------- | ------ | ------- | ------------------------------------------ |
| `children`  | Node   | —       | Content to render inside the article.      |
| `className`| string | —       | Optional CSS class for the article element.|

Focus is applied only to the first element matching: `input` (not hidden/disabled), `select`, `textarea`, `button`, or `[tabindex]` (excluding `tabindex="-1"`). If none is found, nothing is focused.
