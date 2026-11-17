The component crops a given string in the middle, if it gets too long. It even is responsive.

```
<div style={{maxWidth: '400px'}}>
    <CroppedText>The great next sentence is false. The previous great sentence is really great and true.</CroppedText>
</div>
```

Short texts are shown completely, numbers are accepted as well.

```
<div style={{maxWidth: '400px'}}>
    <CroppedText>Short text</CroppedText>
    <CroppedText>{1234567890}</CroppedText>
</div>
```
