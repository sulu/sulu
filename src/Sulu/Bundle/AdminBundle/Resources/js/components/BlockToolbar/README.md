This component provide the BlockToolbar used to provide some action based on given blocks.
It is used in the Block field type to provide multi copy and cut functionality.

```javascript
import Translator from '../../utils/Translator/Translator';

Translator.setTranslations({
    'sulu_admin.%count%_selected': '{count} selected',
    'sulu_admin.select_all': 'Select all',
    'sulu_admin.deselect_all': 'Deselect all',
    'sulu_admin.cancel': 'Cancel',
});

const [open, setOpen] = React.useState(true);
const [allSelected, setSelectAll] = React.useState(true);

<div>
    {
        open
            ? <BlockToolbar
                allSelected={allSelected}
                onCancel={() => setOpen(false)}
                onSelectAll={() => setSelectAll(true)}
                onUnselectAll={() => setSelectAll(false)}
                selectedCount={allSelected ? 2 : 0}
                actions={[
                    {
                        label: 'Copy',
                        icon: 'su-copy',
                        handleClick: () => {
                            alert('copy');
                        },
                    },
                    {
                        label: 'Duplicate',
                        icon: 'su-duplicate',
                        handleClick: () => {
                            alert('duplicate');
                        },
                    },
                    {
                        label: 'Cut',
                        icon: 'su-cut',
                        handleClick: () => {
                            alert('cut');
                        },
                    },
                    {
                        label: 'Delete',
                        icon: 'su-trash-alt',
                        handleClick: () => {
                            alert('delete');
                        },
                    },
                ]}
            />
            : <button onClick={() => setOpen(true)} style={{display: 'block', width: '100%', textAlign: 'right', background: 'none', border: 'none', cursor: 'pointer', padding: '12px 10px', textDecoration: 'underline' }}>
                Show BlockToolbar
            </button>
    }
</div>
```

The BlockToolbar has a mode call "sticky" which make it the toolbar wider for this the BlockToolbar
should be wrapped inside the `Sticky` component.

```javascript
import Translator from '../../utils/Translator/Translator';
import Sticky from '../Sticky';

Translator.setTranslations({
    'sulu_admin.%count%_selected': '{count} selected',
    'sulu_admin.select_all': 'Select all',
    'sulu_admin.deselect_all': 'Deselect all',
    'sulu_admin.cancel': 'Cancel',
});

const [allSelected, setSelectAll] = React.useState(true);

<div style={{position: 'relative'}}>
    <Sticky top={10}>
        {
            (isSticky) =>
                <BlockToolbar
                    allSelected={allSelected}
                    mode={isSticky ? 'sticky' : 'static'}
                    onCancel={() => {
                        alert('Cancel');
                    }}
                    onSelectAll={() => setSelectAll(true)}
                    onUnselectAll={() => setSelectAll(false)}
                    selectedCount={allSelected ? 2 : 0}
                    actions={[
                        {
                            label: 'Copy',
                            icon: 'su-copy',
                            handleClick: () => {
                                alert('copy');
                            },
                        },
                        {
                            label: 'Duplicate',
                            icon: 'su-duplicate',
                            handleClick: () => {
                                alert('duplicate');
                            },
                        },
                        {
                            label: 'Cut',
                            icon: 'su-cut',
                            handleClick: () => {
                                alert('cut');
                            },
                        },
                        {
                            label: 'Delete',
                            icon: 'su-trash-alt',
                            handleClick: () => {
                                alert('delete');
                            },
                        },
                    ]}
                />
        }
    </Sticky>
        
    <div style={{height: '200px', background: '#00ccff', marginTop: '10px'}}/>
</div>
```
