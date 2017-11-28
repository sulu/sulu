The `MimeTypeIndicator` component serves as a replacement for uploaded files which does not offer a thumbnail. It offers predefined colors for common file-types.

```javascript
const box = {
    display: 'inline-block',
    marginRight: 20
};

<div>
    <div style={box}>
        <MimeTypeIndicator
            mimeType="application/vnd.ms-excel"
            width={200}
            height={200}
        />
    </div>
    <div style={box}>
        <MimeTypeIndicator
            mimeType="application/random"
            width={180}
            height={120}
        />
    </div>
    <div style={box}>
        <MimeTypeIndicator
            mimeType="audio"
            width={120}
            height={80}
        />
    </div>
    <div style={box}>
        <MimeTypeIndicator
            mimeType="application/vnd.ms-powerpoint"
            width={25}
            height={25}
            iconSize={18}
        />
    </div>
</div>
```
