// @flow
import React from 'react';
import {act, render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import CollapsibleCollection from '../CollapsibleCollection';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

const TWO_COLLAPSIBLES = [
    {subtitle: '3 attributes', title: 'General'},
    {subtitle: '2 attributes', title: 'Marketing'},
];

function renderCollapsibleContent(collapsible) {
    return <div>{collapsible.title} content</div>;
}

function renderCollapsibleCollection(props: Object = {}) {
    const ref: any = React.createRef();
    let currentProps = {
        onChange: jest.fn(),
        renderCollapsibleContent,
        value: TWO_COLLAPSIBLES,
        ...props,
    };

    const {container, rerender} = render(<CollapsibleCollection {...currentProps} ref={ref} />);

    return {
        container,
        ref,
        rerenderCollapsibleCollection: (nextProps: Object) => {
            currentProps = {...currentProps, ...nextProps};

            rerender(<CollapsibleCollection {...currentProps} ref={ref} />);
        },
        user: userEvent.setup(),
    };
}

function getCollapsible(title: string) {
    // $FlowFixMe
    return screen.getByText(title).closest('section');
}

test('Render two expanded collapsibles', () => {
    const {container} = renderCollapsibleCollection();

    expect(container).toMatchSnapshot();
});

test('Render all collapsibles expanded by default', () => {
    renderCollapsibleCollection();

    expect(screen.getByText('General content')).toBeInTheDocument();
    expect(screen.getByText('Marketing content')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.collapse_all')).toBeInTheDocument();
});

test('Render the title and the subtitle of every collapsible', () => {
    renderCollapsibleCollection();

    expect(screen.getByText('General')).toBeInTheDocument();
    expect(screen.getByText('3 attributes')).toBeInTheDocument();
    expect(screen.getByText('Marketing')).toBeInTheDocument();
    expect(screen.getByText('2 attributes')).toBeInTheDocument();
});

test('Call the renderCollapsibleContent callback with the value, the index and the expanded state', () => {
    const renderSpy = jest.fn((collapsible) => <div>{collapsible.title} content</div>);
    renderCollapsibleCollection({renderCollapsibleContent: renderSpy});

    expect(renderSpy).toHaveBeenCalledWith(TWO_COLLAPSIBLES[0], 0, true);
    expect(renderSpy).toHaveBeenCalledWith(TWO_COLLAPSIBLES[1], 1, true);
});

test('Clicking collapse all should collapse every collapsible and flip the toggle', async() => {
    const {user} = renderCollapsibleCollection();

    await user.click(screen.getByText('sulu_admin.collapse_all'));

    expect(screen.queryByText('General content')).not.toBeInTheDocument();
    expect(screen.queryByText('Marketing content')).not.toBeInTheDocument();
    expect(screen.getByText('sulu_admin.expand_all')).toBeInTheDocument();
});

test('Clicking expand all should expand every collapsible again', async() => {
    const {user} = renderCollapsibleCollection();

    await user.click(screen.getByText('sulu_admin.collapse_all'));
    await user.click(screen.getByText('sulu_admin.expand_all'));

    expect(screen.getByText('General content')).toBeInTheDocument();
    expect(screen.getByText('Marketing content')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.collapse_all')).toBeInTheDocument();
});

test('Collapsing a single collapsible should leave the other ones untouched', async() => {
    const {user} = renderCollapsibleCollection();

    await user.click(within(getCollapsible('General')).getByLabelText('su-collapse-vertical'));

    expect(screen.queryByText('General content')).not.toBeInTheDocument();
    expect(screen.getByText('Marketing content')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.collapse_all')).toBeInTheDocument();
});

test('Expanding a single collapsible again should render its content', async() => {
    const {user} = renderCollapsibleCollection();

    await user.click(within(getCollapsible('General')).getByLabelText('su-collapse-vertical'));
    await user.click(within(getCollapsible('General')).getByLabelText('su-expand-vertical'));

    expect(screen.getByText('General content')).toBeInTheDocument();
});

test('Show the expand all toggle when every collapsible was collapsed individually', async() => {
    const {user} = renderCollapsibleCollection();

    await user.click(within(getCollapsible('General')).getByLabelText('su-collapse-vertical'));
    await user.click(within(getCollapsible('Marketing')).getByLabelText('su-collapse-vertical'));

    expect(screen.getByText('sulu_admin.expand_all')).toBeInTheDocument();
});

test('Do not render the collapse all toggle for a single collapsible', () => {
    renderCollapsibleCollection({value: [{title: 'General'}]});

    expect(screen.queryByText('sulu_admin.collapse_all')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.expand_all')).not.toBeInTheDocument();
});

test('Do not render the collapse all toggle without a value', () => {
    renderCollapsibleCollection({value: []});

    expect(screen.queryByText('sulu_admin.collapse_all')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.expand_all')).not.toBeInTheDocument();
});

test('Render the actions and call their callback with the index of the collapsible', async() => {
    const deleteSpy = jest.fn();
    const {user} = renderCollapsibleCollection({
        actions: [{icon: 'su-trash-alt', label: 'Delete', onClick: deleteSpy}],
    });

    await user.click(within(getCollapsible('Marketing')).getByLabelText('Delete'));

    expect(deleteSpy).toHaveBeenCalledWith(1);
    expect(screen.getByText('Marketing content')).toBeInTheDocument();
});

test('Render a drag handle for every collapsible', () => {
    renderCollapsibleCollection();

    expect(screen.queryAllByLabelText('su-more')).toHaveLength(2);
});

test('Do not render a drag handle when the collection is not movable', () => {
    renderCollapsibleCollection({movable: false});

    expect(screen.queryAllByLabelText('su-more')).toHaveLength(0);
});

test('Sorting a collapsible should reorder the value and move its expanded state along', async() => {
    const changeSpy = jest.fn();
    const sortEndSpy = jest.fn();
    const value = [{title: 'General'}, {title: 'Marketing'}, {title: 'Shipping'}];
    const {ref, rerenderCollapsibleCollection, user} = renderCollapsibleCollection({
        onChange: changeSpy,
        onSortEnd: sortEndSpy,
        value,
    });

    await user.click(within(getCollapsible('General')).getByLabelText('su-collapse-vertical'));

    act(() => {
        ref.current.handleSortEnd({newIndex: 2, oldIndex: 0});
    });

    expect(changeSpy).toHaveBeenCalledWith([{title: 'Marketing'}, {title: 'Shipping'}, {title: 'General'}]);
    expect(sortEndSpy).toHaveBeenCalledWith(0, 2);

    rerenderCollapsibleCollection({value: [{title: 'Marketing'}, {title: 'Shipping'}, {title: 'General'}]});

    expect(screen.queryByText('General content')).not.toBeInTheDocument();
    expect(screen.getByText('Marketing content')).toBeInTheDocument();
    expect(screen.getByText('Shipping content')).toBeInTheDocument();
});

test('Render the given texts instead of the default translations', async() => {
    const {user} = renderCollapsibleCollection({
        addButtonText: 'Add attributes',
        collapseAllText: 'Collapse all groups',
        expandAllText: 'Expand all groups',
        onAddClick: jest.fn(),
    });

    expect(screen.getByText('Add attributes')).toBeInTheDocument();
    expect(screen.getByText('Collapse all groups')).toBeInTheDocument();

    await user.click(screen.getByText('Collapse all groups'));

    expect(screen.getByText('Expand all groups')).toBeInTheDocument();
});

test('Do not render an add button when no onAddClick callback is given', () => {
    renderCollapsibleCollection();

    expect(screen.queryByText('sulu_admin.add')).not.toBeInTheDocument();
});

test('Clicking the add button should call the onAddClick callback', async() => {
    const addSpy = jest.fn();
    const {user} = renderCollapsibleCollection({onAddClick: addSpy});

    await user.click(screen.getByText('sulu_admin.add'));

    expect(addSpy).toHaveBeenCalled();
});

test('Render the given add button text instead of the default translation', () => {
    renderCollapsibleCollection({addButtonText: 'Add attributes', onAddClick: jest.fn()});

    expect(screen.getByText('Add attributes')).toBeInTheDocument();
});

test('An appended collapsible should start expanded while collapsed siblings stay collapsed', async() => {
    const {rerenderCollapsibleCollection, user} = renderCollapsibleCollection();

    await user.click(screen.getByText('sulu_admin.collapse_all'));

    rerenderCollapsibleCollection({value: [...TWO_COLLAPSIBLES, {title: 'Shipping'}]});

    expect(screen.getByText('Shipping content')).toBeInTheDocument();
    expect(screen.queryByText('General content')).not.toBeInTheDocument();
    expect(screen.queryByText('Marketing content')).not.toBeInTheDocument();
});

test('A removed collapsible should not leave its expanded state behind', async() => {
    const value = [{title: 'General'}, {title: 'Marketing'}, {title: 'Shipping'}];
    const {rerenderCollapsibleCollection, user} = renderCollapsibleCollection({value});

    await user.click(within(getCollapsible('Shipping')).getByLabelText('su-collapse-vertical'));

    rerenderCollapsibleCollection({value: [{title: 'General'}, {title: 'Marketing'}]});
    rerenderCollapsibleCollection({value});

    expect(screen.getByText('Shipping content')).toBeInTheDocument();
});
