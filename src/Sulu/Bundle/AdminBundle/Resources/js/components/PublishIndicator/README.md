The `PublishIndicator` is a simple component, which can be used to show the draft/publish state of an entity.

The `published` state is represented by a green circle.

```
<div style={{width: '10px'}}>
    <PublishIndicator published={true} />
</div>
```

The `draft` state is represented by a yellow circle.

```
<div style={{width: '10px'}}>
    <PublishIndicator draft={true} />
</div>
```

If something was already published and another draft was saved afterwards, then a yellow and green circle is shown.

```
<div style={{width: '10px'}}>
    <PublishIndicator draft={true} published={true} />
</div>
```
