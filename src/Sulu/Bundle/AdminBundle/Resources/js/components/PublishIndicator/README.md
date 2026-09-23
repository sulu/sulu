The `PublishIndicator` is a simple component, which can be used to show the draft/publish state of an entity.

The `published` state is represented by a green circle.

```
<div style={{width: '10px'}}>
    <PublishIndicator published={true} />
</div>
```

The `draft` state is represented by a grey circle.

```
<div style={{width: '10px'}}>
    <PublishIndicator draft={true} />
</div>
```

If something was already published and another draft was saved afterwards, then a grey and green circle is shown.

```
<div style={{width: '10px'}}>
    <PublishIndicator draft={true} published={true} />
</div>
```

A pending review is represented by a yellow circle, on its own while the content has never been
published, and beside the green one once it has.

```
<div style={{width: '40px', display: 'flex', gap: '10px'}}>
    <PublishIndicator review={true} />
    <PublishIndicator review={true} published={true} />
</div>
```

The component takes the three flags, not a workflow place. Use the `getWorkflowDots` util to turn a
place into them: `unpublished` and `draft` set `draft`, `review` and `review_draft` set `review`, and
content carrying no place at all falls back to the `draft` and `published` flags it is given.
