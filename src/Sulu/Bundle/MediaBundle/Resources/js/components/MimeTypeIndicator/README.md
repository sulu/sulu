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
            mimeType="application/msword"
            width={200}
            height={150}
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
