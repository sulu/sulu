The Toolbar is a configurable component which serves as a wrapper for multiple other component types. 
The configuration can be set by using the `withToolbar` function which takes a React component as the first and a configuration object as the second argument.
In most cases the given component would be some kind of a page or view component in which the Toolbar will be placed.

The configuration object describes how the Toolbar should be rendered and the kind of inputs and controls it should offer.

Here is a basic example of the Toolbar component:

```
const withToolbar = require('./withToolbar').default;
const Toolbar = require('./Toolbar').default;

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
                icon: 'plus-circle',
                onClick: () => {
                    alert('Hi!');
                },
            },
        ],
    };
});

<div>
    <Toolbar />
    <PageWithToolbar />
</div>
```

In the above example an item with the type of button is being defined inside the configuration. The Toolbar offers three kinds of items which can be used by defining them inside the items array and setting the appropriate type:
 - button
 - dropdown
 - select

Here a Toolbar example which is using all three of them:

```
const withToolbar = require('./withToolbar').default;
const Toolbar = require('./Toolbar').default;

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
                icon: 'smile-o',
                onClick: () => {
                    alert('What do you expect me to do?!');
                },
            },
            {
                type: 'dropdown',
                label: 'Mr. Dropdown',                
                icon: 'smile-o',
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
                icon: 'venus',
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
});

<div>
    <Toolbar />
    <PageWithToolbar />
</div>
```

In addition to items, the Toolbar also offers a set of control elements like a back-button, a language-chooser and the option to show icons in order to display notifications or other state information about the application.

Example with special control elements:

```
const withToolbar = require('./withToolbar').default;
const Toolbar = require('./Toolbar').default;

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
                icon: 'star',
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
            'bell-o',
            'commenting',
            'exclamation-circle',
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
});

<div>
    <Toolbar />
    <PageWithToolbar />
</div>
```
