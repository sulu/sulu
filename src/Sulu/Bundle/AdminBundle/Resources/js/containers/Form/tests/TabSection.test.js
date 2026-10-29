// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import ResourceStore from '../../../stores/ResourceStore';
import TabSection from '../TabSection';
import FormInspector from '../FormInspector';
import conditionDataProviderRegistry from '../registries/conditionDataProviderRegistry';
import ResourceFormStore from '../stores/ResourceFormStore';

jest.mock('../../../stores/ResourceStore', () => jest.fn());
jest.mock('../FormInspector', () => jest.fn());
jest.mock('../stores/ResourceFormStore', () => jest.fn());

window.ResizeObserver = jest.fn(function() {
    this.observe = jest.fn();
    this.disconnect = jest.fn();
});

const schema = {
    label: 'Tabs',
    layout: 'tabs',
    type: 'section',
    items: {
        title: {label: 'Title', type: 'text_line'},
        content: {
            label: 'Content',
            type: 'section',
            items: {
                text: {label: 'Text', type: 'text_editor'},
                blocks: {label: 'Blocks', type: 'block'},
            },
        },
        settings: {
            label: 'Settings',
            type: 'section',
            items: {
                anchor: {label: 'Anchor', type: 'text_line'},
                advanced: {
                    label: 'Advanced',
                    type: 'section',
                    items: {
                        cssClass: {label: 'CSS class', type: 'text_line'},
                    },
                },
            },
        },
    },
};

function renderItem(schemaField, schemaKey, schemaPath) {
    return <div data-schema-path={schemaPath} data-testid={'item-' + schemaKey} key={schemaKey} />;
}

function renderTabSection(props: Object = {}) {
    return render(
        <TabSection
            data={{}}
            formInspector={new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'))}
            name="tabs"
            renderItem={renderItem}
            schema={schema}
            schemaPath="/tabs"
            showAllErrors={false}
            {...props}
        />
    );
}

beforeEach(() => {
    conditionDataProviderRegistry.clear();
});

test('Render the child sections as tabs and the other items above the tab bar', () => {
    renderTabSection();

    expect(screen.getByText('Tabs')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Content'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Settings'})).toBeInTheDocument();

    expect(screen.getByTestId('item-title')).toHaveAttribute('data-schema-path', '/tabs/items/title');
    expect(screen.getByTestId('item-text')).toHaveAttribute('data-schema-path', '/tabs/items/content/items/text');
    expect(screen.getByTestId('item-anchor')).toHaveAttribute('data-schema-path', '/tabs/items/settings/items/anchor');
});

test('Keep the items of all tabs rendered and hide the ones of the unselected tabs', async() => {
    const user = userEvent.setup();
    renderTabSection();

    expect(screen.getByTestId('item-text').parentElement).not.toHaveClass('hidden');
    expect(screen.getByTestId('item-anchor').parentElement).toHaveClass('hidden');

    await user.click(screen.getByRole('button', {name: 'Settings'}));

    expect(screen.getByTestId('item-text').parentElement).toHaveClass('hidden');
    expect(screen.getByTestId('item-anchor').parentElement).not.toHaveClass('hidden');
});

test('Do not render a tab whose visibleCondition evaluates to false', () => {
    renderTabSection({
        data: {showSettings: false},
        schema: {
            ...schema,
            items: {
                ...schema.items,
                settings: {...schema.items.settings, visibleCondition: 'showSettings == true'},
            },
        },
    });

    expect(screen.getByRole('button', {name: 'Content'})).toBeInTheDocument();
    expect(screen.queryByRole('button', {name: 'Settings'})).not.toBeInTheDocument();
    expect(screen.queryByTestId('item-anchor')).not.toBeInTheDocument();
});

test('Select the first tab when the selected tab disappears', async() => {
    const user = userEvent.setup();
    const settingsSchema = {
        ...schema,
        items: {
            ...schema.items,
            settings: {...schema.items.settings, visibleCondition: 'showSettings == true'},
        },
    };
    const {rerender} = renderTabSection({data: {showSettings: true}, schema: settingsSchema});

    await user.click(screen.getByRole('button', {name: 'Settings'}));

    rerender(
        <TabSection
            data={{showSettings: false}}
            formInspector={new FormInspector(new ResourceFormStore(new ResourceStore('snippets'), 'snippets'))}
            name="tabs"
            renderItem={renderItem}
            schema={settingsSchema}
            schemaPath="/tabs"
            showAllErrors={false}
        />
    );

    expect(screen.getByTestId('item-text').parentElement).not.toHaveClass('hidden');
});

test('Render nothing if the visibleCondition of the tab section evaluates to false', () => {
    renderTabSection({data: {showTabs: false}, schema: {...schema, visibleCondition: 'showTabs == true'}});

    expect(screen.queryByText('Tabs')).not.toBeInTheDocument();
    expect(screen.queryByTestId('item-title')).not.toBeInTheDocument();
});

test('Show the number of errors of every tab when showing all errors', () => {
    renderTabSection({
        errors: {
            title: {keyword: 'required', parameters: {}},
            text: {keyword: 'required', parameters: {}},
            blocks: [
                {headline: {keyword: 'required', parameters: {}}},
                undefined,
                {headline: {keyword: 'minLength', parameters: {}}},
            ],
            cssClass: {keyword: 'pattern', parameters: {}},
        },
        showAllErrors: true,
    });

    expect(screen.getByRole('button', {name: 'Content 3'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Settings 1'})).toBeInTheDocument();
});

test('Do not show the number of errors before showing all errors', () => {
    renderTabSection({
        errors: {text: {keyword: 'required', parameters: {}}},
        showAllErrors: false,
    });

    expect(screen.getByRole('button', {name: 'Content'})).toBeInTheDocument();
    expect(screen.queryByText('1')).not.toBeInTheDocument();
});
