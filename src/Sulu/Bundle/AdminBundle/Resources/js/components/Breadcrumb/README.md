The `Breadcrumb` indicates the current page’s location within a navigational hierarchy.

```
const handleClick = (value) => {
    alert(`You clicked on crumb ${value}`);
};

<Breadcrumb onItemClick={handleClick}>
    <Breadcrumb.Item>Root Directory</Breadcrumb.Item>
    <Breadcrumb.Item value={2}>...</Breadcrumb.Item>
    <Breadcrumb.Item>Current Directory</Breadcrumb.Item>
</Breadcrumb>
```
