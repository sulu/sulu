The `Breadcrumb` indicates the current page’s location within a navigational hierarchy.

```
const handleClick1 = () => {
    alert(`You clicked on crumb 1`);
};
const handleClick2 = (value) => {
    alert(`You clicked on crumb ${value}`);
};

<Breadcrumb>
    <Breadcrumb.Crumb onClick={handleClick1}>Root Directory</Breadcrumb.Crumb>
    <Breadcrumb.Crumb value={2} onClick={handleClick2}>...</Breadcrumb.Crumb>
    <Breadcrumb.Crumb>Current Directory</Breadcrumb.Crumb>
</Breadcrumb>
```
