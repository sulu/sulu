This component shows how many segments are left to type. A segment is a part of a text, which is delimited by a passed
delimiter. Therefore the components needs the full string and the delimiter in the string. That's e.g. useful if the user
should fill 5 words split by a comma.

```javascript
<SegmentCounter delimiter="," max={5} value="keyword1, keyword2" />
```

If too many segments have been used the component will show this with a red font.

```javascript
<SegmentCounter delimiter="," max={1} value="keyword1, keyword2" />
```
