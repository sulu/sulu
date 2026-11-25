The `Breadcrumb` indicates the current page’s location within a navigational hierarchy.

```javascript
const handleClick = (value) => {
    alert(`You clicked on crumb ${value}`);
};

<Breadcrumb onItemClick={handleClick}>
    <Breadcrumb.Item>Root Directory</Breadcrumb.Item>
    <Breadcrumb.Item value={2}>...</Breadcrumb.Item>
    <Breadcrumb.Item>Current Directory</Breadcrumb.Item>
</Breadcrumb>
```

Without an `onItemClick` handler the items are still rendered; the last item is never clickable.

```javascript
<Breadcrumb>
    <Breadcrumb.Item>Home</Breadcrumb.Item>
    <Breadcrumb.Item value="section">Section</Breadcrumb.Item>
    <Breadcrumb.Item>Page</Breadcrumb.Item>
</Breadcrumb>
```
