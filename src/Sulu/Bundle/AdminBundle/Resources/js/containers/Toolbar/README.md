The `Toolbar` is a configurable component which serves as a wrapper for multiple other component types. 
The configuration can be set by using the `withToolbar` function which takes a React component as the first, a 
configuration object as the second and optionally the `storeKey` of the store you want to use as the third argument. 
If you don't define a `storeKey` the default store will be used. In most cases the given component would be some kind 
of a page or view component in which the Toolbar will be placed.

The configuration object describes how the Toolbar should be rendered and the kind of inputs and controls it should 
offer.

Here is a basic example of the `Toolbar` component:

```javascript
import withToolbar from './withToolbar';

class Page extends React.PureComponent {
    render() {
        return (
            <h1>My awesome Page</h1>
        );
    }
}

const PageWithToolbar = withToolbar(Page, function() {
    return {
        items: [
            {
                type: 'button',
                value: 'Click me!',
                icon: 'fa-plus-circle',
                onClick: () => {
                    alert('Hi!');
                },
            },
        ],
    };
}, 'toolbar-demo-1');

// instead of this mocked Router you would usually use a real one
const router = {
    addUpdateRouteHook: () => () => undefined,
};

<div style={{overflow: 'hidden'}}>
    <Toolbar storeKey="toolbar-demo-1" />
    <PageWithToolbar router={router} />
</div>
```

In the above example an item with the type of button is being defined inside the configuration. The `Toolbar` 
offers three kinds of items which can be used by defining them inside the items array and setting the appropriate type:
 - button
 - dropdown
 - select

Here a `Toolbar` example which is using all three of them:

```javascript
import withToolbar from './withToolbar';

initialState = {selectVal: 1}

class Page extends React.PureComponent {
    render() {
        return (
            <h1>Whoat?! The Toolbar has 3 different items!</h1>
        );
    }
}

const PageWithToolbar = withToolbar(Page, function() {
    return {
        items: [
            {
                type: 'button',
                value: 'I\'m a Button',
                icon: 'fa-smile-o',
                onClick: () => {
                    alert('What do you expect me to do?!');
                },
            },
            {
                type: 'dropdown',
                label: 'Mr. Dropdown',                
                icon: 'fa-smile-o',
                options: [
                    {
                        label: 'Now click me!',
                        onClick: () => {
                            alert('Hah! You just took commands from an option.');
                        },
                    },
                    {
                        disabled: true,
                        label: 'Who disabled me?!',
                        onClick: () => {
                            alert('Disabled onClicks won`\t fire.');
                        },
                    },
                ],
            },
            {
                type: 'select',
                label: 'Mrs. Select',
                value: state.selectVal,
                icon: 'fa-venus',
                onChange: (value) => {
                    setState({selectVal: value});
                },
                options: [
                    {
                        value: 'first',
                        label: 'The dropdown is so silly...',
                    },
                    {
                        value: 2,
                        label: 'Selects are much cooler!',
                    },
                ],
            },
        ],
    };
}, 'toolbar-demo-2');

// instead of this mocked Router you would usually use a real one
const router = {
    addUpdateRouteHook: () => () => undefined,
};

<div style={{overflow: 'hidden'}}>
    <Toolbar storeKey="toolbar-demo-2" />
    <PageWithToolbar router={router} />
</div>
```

In addition to items, the `Toolbar` also offers a set of control elements like a back-button, a language-chooser 
and the option to show icons in order to display notifications or other state information about the application.

Example with special control elements:

```javascript
import withToolbar from './withToolbar';
import Icon from '../../components/Icon';

initialState = {localeVal: 'en'};

class Page extends React.PureComponent {
    render() {
        return (
            <h1>I love this Toolbar!</h1>
        );
    }
}

const PageWithToolbar = withToolbar(Page, function() {
    return {
        items: [
            {
                type: 'button',
                value: 'Simple button',
                icon: 'fa-star',
                onClick: () => {
                    alert(':)');
                },
            },
        ],
        backButton: {
            onClick: () => {
                alert('You choose what to do!');
            }
        },
        icons: [
            <Icon key="fa-bell-o" name="fa-bell-o" />,
            <Icon key="fa-commenting" name="fa-commenting" />,
            <Icon key="fa-exclamation-circle" name="fa-exclamation-circle" />,
        ],
        locale: {
            value: state.localeVal,
            onChange: (value) => {
                setState({localeVal: value});
            },
            options: [
                {
                    value: 'en',
                    label: 'en',
                },
                {
                    value: 'fr',
                    label: 'fr',
                },
                {
                    value: 'de',
                    label: 'de',
                },
            ],
        },
    };
}, 'toolbar-demo-3');

// instead of this mocked Router you would usually use a real one
const router = {
    addUpdateRouteHook: () => () => undefined,
};

<div style={{overflow: 'hidden'}}>
    <Toolbar storeKey="toolbar-demo-3" />
    <PageWithToolbar router={router} />
</div>
```

Each item has also the possibility to show a loader instead of its text by using the `loading` property. This is useful
when an asynchronous action is sent:

```javascript
import withToolbar from './withToolbar';
import {observable} from 'mobx';

class Page extends React.PureComponent {
    constructor() {
        this.loadingSave = observable.box(false);
        this.template = observable.box();
        this.loadingTemplate = observable.box(false);
        this.loadingActions = observable.box(false);
    }

    render() {
        return (
            <h1>My awesome Page</h1>
        );
    }
}

const PageWithToolbar = withToolbar(Page, function() {
    return {
        items: [
            {
                type: 'button',
                value: 'Save',
                icon: 'fa-floppy-o',
                loading: this.loadingSave.get(),
                onClick: () => {
                    this.loadingSave.set(true);
                    setTimeout(() => {
                        this.loadingSave.set(false);
                    }, 2000);
                },
            },
            {
                type: 'select',
                label: 'Template',
                value: this.template.get(),
                icon: 'fa-pencil',
                loading: this.loadingTemplate.get(),
                onChange: (value) => {
                    this.loadingTemplate.set(true);
                    setTimeout(() => {
                        this.template.set(value);
                        this.loadingTemplate.set(false);
                    }, 2000);
                },
                options: [
                    {
                        value: 'default',
                        label: 'Default',
                    },
                    {
                        value: 'news',
                        label: 'News',
                    },
                ],
            },
            {
                type: 'dropdown',
                label: 'Actions',
                icon: 'fa-ellipsis-h',
                loading: this.loadingActions.get(),
                options: [
                    {
                        label: 'Copy',
                        onClick: () => {
                            this.loadingActions.set(true);
                            setTimeout(() => {
                                this.loadingActions.set(false);
                            }, 2000);
                        }
                    }
                ],
            },
        ],
    };
}, 'toolbar-demo-4');

// instead of this mocked Router you would usually use a real one
const router = {
    addUpdateRouteHook: () => () => undefined,
};

<div style={{overflow: 'hidden'}}>
    <Toolbar storeKey="toolbar-demo-4" />
    <PageWithToolbar router={router} />
</div>
```

The `errors` property can be set to show an error instead of the toolbar.

```javascript
import withToolbar from './withToolbar';

class Page extends React.PureComponent {
    render() {
        return (
            <h1>Just an error...</h1>
        );
    }
}

const PageWithToolbar = withToolbar(Page, function() {
    return {
        errors: [
            {code: 1000},
        ],
    };
}, 'toolbar-demo-5');

// instead of this mocked Router you would usually use a real one
const router = {
    addUpdateRouteHook: () => () => undefined,
};

<div style={{overflow: 'hidden'}}>
    <Toolbar storeKey="toolbar-demo-5" />
    <PageWithToolbar router={router} />
</div>
```

Corresponding to that the `showSuccess` property can be used to show a success icon for 1.5 seconds.

```javascript
import withToolbar from './withToolbar';
import {extendObservable, observable} from 'mobx';

class Page extends React.PureComponent {
    render() {
        return (
            <h1>Just a success :-)</h1>
        );
    }
}

const PageWithToolbar = withToolbar(Page, function() {
    return {
        showSuccess: observable.box(true),
    };
}, 'toolbar-demo-6');

// instead of this mocked Router you would usually use a real one
const router = {
    addUpdateRouteHook: () => () => undefined,
};

<div style={{overflow: 'hidden'}}>
    <Toolbar storeKey="toolbar-demo-6" />
    <PageWithToolbar router={router} />
</div>
```
