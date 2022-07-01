This component provides Sticky functionality to a postion relative parent container.

This component is only required when the sticky container need different styling
between stuck or unstuck mode. For all other cases the component is not required
and a simple `position: sticky` CSS should be used.

A related CSS issue can be found in [w3c/csswg-drafts](https://github.com/w3c/csswg-drafts/issues/5979).

The component implementation is inspired by a post on the [Chrome Developers Blog](https://developer.chrome.com/blog/sticky-headers/)
and was simplified for our use case.

```javascript
<div style={{position: 'relative'}}>
    <Sticky>
        {
            (isSticky) => <div style={{background: (isSticky ? '#cc00ff' : '#00ccff'), color: (isSticky ? 'white' : 'black'), padding: '20px'}}>
                {isSticky ? 'Container is sticky!' : 'Container is unsticky!'}
            </div>
        }
    </Sticky>
    
    <div style={{height: '150px', marginTop: '10px', background: '#ffcc00'}}>
        
    </div>
</div>
```

Via the "top" property it can be controlled the position to the sticked container.

```javascript
<div style={{position: 'relative'}}>
    <Sticky top={20}>
        {
            (isSticky) => <div style={{background: (isSticky ? '#cc00ff' : '#00ccff'), color: (isSticky ? 'white' : 'black'), padding: '20px'}}>
                {isSticky ? 'Container is sticky!' : 'Container is unsticky!'}
            </div>
        }
    </Sticky>
    
    <div style={{height: '150px', marginTop: '10px', background: '#ffcc00'}}>
        
    </div>
</div>
```
