The `SearchResult` component renders a preview of how a page looks in a search engine result. It is used in the SEO
section of the page form. The `title`, `url` and `description` props are all optional and may be `null` or `undefined`,
in which case the corresponding line stays empty.

```javascript
<SearchResult
    description="Sulu is a highly extensible, open-source PHP content management system based on the Symfony framework."
    title="Sulu - Open-Source CMS"
    url="https://sulu.io/en"
/>
```

Without a description or url only the given lines are shown:

```javascript
<div>
    <SearchResult title="Only a title" />
    <SearchResult description="Only a description" url="https://sulu.io" />
</div>
```
