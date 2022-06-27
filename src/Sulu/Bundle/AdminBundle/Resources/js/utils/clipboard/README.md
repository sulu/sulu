The `clipboard` module provides a virtual clipboard that can be used for implementing a copy and paste
functionality. For example, the module is used by the `BlockCollection` component to allow for copying
block from one form to another form. 

The `clipboard` module uses keys to allow for storing multiple entries at the same time. Its data is
stored in the `localStorage`, which makes it possible to access the same data in different browser
tabs.

Storing data to the clipboard is possible by using the `set` method:

```javascript static
import clipboard from './clipboard';

clipboard.set('clipboard-entry-key', 'clipboard content');
```

Reading data from the clipboard is done using the `observe` method. It accepts an observer function
that is called each time the data in the clipboard changes. This makes it easy to update the user
interface if the data in the clipboard is changed. 

It is possible to invoke the observer function immediately with the current value by setting 
the `invokeImmediately` to true.
Additionally, the `observe` method returns a disposer function that should be called to stop 
observing changes in the clipboard.

```javascript static
import clipboard from './clipboard';

const disposer = clipboard.observe('clipboard-entry-key', (data) => {
    console.log('new clipboard content', data)
}));

clipboard.set('clipboard-entry-key', 'updated content');

disposer();
```
