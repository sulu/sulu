// @flow
import React from 'react';
import {render, screen, within} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Collapsible from '../../Collapsible';
import CollapsibleCollection from '../CollapsibleCollection';

jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

function getCollapsible(title: string) {
    // $FlowFixMe
    return screen.getByText(title).closest('section');
}

function renderTwoCollapsibles(props: Object = {}) {
    return render(
        <CollapsibleCollection {...props}>
            <Collapsible title="General">
                <div>General content</div>
            </Collapsible>
            <Collapsible title="Marketing">
                <div>Marketing content</div>
            </Collapsible>
        </CollapsibleCollection>
    );
}

test('Render two expanded collapsibles', () => {
    const {container} = renderTwoCollapsibles();

    expect(container).toMatchSnapshot();
});

test('Render all children expanded by default', () => {
    renderTwoCollapsibles();

    expect(screen.getByText('General content')).toBeInTheDocument();
    expect(screen.getByText('Marketing content')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.collapse_all')).toBeInTheDocument();
});

test('Clicking collapse all should collapse every child and flip the toggle', async() => {
    const user = userEvent.setup();
    renderTwoCollapsibles();

    await user.click(screen.getByText('sulu_admin.collapse_all'));

    expect(screen.queryByText('General content')).not.toBeInTheDocument();
    expect(screen.queryByText('Marketing content')).not.toBeInTheDocument();
    expect(screen.getByText('sulu_admin.expand_all')).toBeInTheDocument();
});

test('Clicking expand all should expand every child again', async() => {
    const user = userEvent.setup();
    renderTwoCollapsibles();

    await user.click(screen.getByText('sulu_admin.collapse_all'));
    await user.click(screen.getByText('sulu_admin.expand_all'));

    expect(screen.getByText('General content')).toBeInTheDocument();
    expect(screen.getByText('Marketing content')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.collapse_all')).toBeInTheDocument();
});

test('Collapsing a single child should leave the other children untouched', async() => {
    const user = userEvent.setup();
    renderTwoCollapsibles();

    await user.click(within(getCollapsible('General')).getByLabelText('su-collapse-vertical'));

    expect(screen.queryByText('General content')).not.toBeInTheDocument();
    expect(screen.getByText('Marketing content')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.collapse_all')).toBeInTheDocument();
});

test('Expanding a single child again should render its content', async() => {
    const user = userEvent.setup();
    renderTwoCollapsibles();

    await user.click(within(getCollapsible('General')).getByLabelText('su-collapse-vertical'));
    await user.click(within(getCollapsible('General')).getByLabelText('su-expand-vertical'));

    expect(screen.getByText('General content')).toBeInTheDocument();
});

test('Show the expand all toggle when every child was collapsed individually', async() => {
    const user = userEvent.setup();
    renderTwoCollapsibles();

    await user.click(within(getCollapsible('General')).getByLabelText('su-collapse-vertical'));
    await user.click(within(getCollapsible('Marketing')).getByLabelText('su-collapse-vertical'));

    expect(screen.getByText('sulu_admin.expand_all')).toBeInTheDocument();
});

test('Do not render an add button when no onAddClick callback is given', () => {
    renderTwoCollapsibles();

    expect(screen.queryByText('sulu_admin.add')).not.toBeInTheDocument();
});

test('Clicking the add button should call the onAddClick callback', async() => {
    const addSpy = jest.fn();
    const user = userEvent.setup();
    renderTwoCollapsibles({onAddClick: addSpy});

    await user.click(screen.getByText('sulu_admin.add'));

    expect(addSpy).toHaveBeenCalledWith();
});

test('Render the given texts instead of the default translations', async() => {
    const user = userEvent.setup();
    renderTwoCollapsibles({
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

test('Do not render the collapse all toggle without children', () => {
    render(<CollapsibleCollection />);

    expect(screen.queryByText('sulu_admin.collapse_all')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.expand_all')).not.toBeInTheDocument();
});

test('Removing a middle child should keep the collapsed state attached to the right card', async() => {
    const user = userEvent.setup();
    const {rerender} = render(
        <CollapsibleCollection>
            <Collapsible key="general" title="General">
                <div>General content</div>
            </Collapsible>
            <Collapsible key="marketing" title="Marketing">
                <div>Marketing content</div>
            </Collapsible>
            <Collapsible key="shipping" title="Shipping">
                <div>Shipping content</div>
            </Collapsible>
        </CollapsibleCollection>
    );

    await user.click(within(getCollapsible('Shipping')).getByLabelText('su-collapse-vertical'));

    expect(screen.queryByText('Shipping content')).not.toBeInTheDocument();

    rerender(
        <CollapsibleCollection>
            <Collapsible key="general" title="General">
                <div>General content</div>
            </Collapsible>
            <Collapsible key="shipping" title="Shipping">
                <div>Shipping content</div>
            </Collapsible>
        </CollapsibleCollection>
    );

    expect(screen.getByText('General content')).toBeInTheDocument();
    expect(screen.queryByText('Shipping content')).not.toBeInTheDocument();
});

test('A falsy child alongside a real one should render without throwing', () => {
    render(
        <CollapsibleCollection>
            <Collapsible key="general" title="General">
                <div>General content</div>
            </Collapsible>
            {false && (
                <Collapsible key="marketing" title="Marketing">
                    <div>Marketing content</div>
                </Collapsible>
            )}
        </CollapsibleCollection>
    );

    expect(screen.getByText('General content')).toBeInTheDocument();
    expect(screen.queryByText('Marketing content')).not.toBeInTheDocument();
});

test('An appended child should start expanded while collapsed siblings stay collapsed', async() => {
    const user = userEvent.setup();
    const {rerender} = render(
        <CollapsibleCollection>
            <Collapsible key="general" title="General">
                <div>General content</div>
            </Collapsible>
            <Collapsible key="marketing" title="Marketing">
                <div>Marketing content</div>
            </Collapsible>
        </CollapsibleCollection>
    );

    await user.click(screen.getByText('sulu_admin.collapse_all'));

    rerender(
        <CollapsibleCollection>
            <Collapsible key="general" title="General">
                <div>General content</div>
            </Collapsible>
            <Collapsible key="marketing" title="Marketing">
                <div>Marketing content</div>
            </Collapsible>
            <Collapsible key="shipping" title="Shipping">
                <div>Shipping content</div>
            </Collapsible>
        </CollapsibleCollection>
    );

    expect(screen.getByText('Shipping content')).toBeInTheDocument();
    expect(screen.queryByText('General content')).not.toBeInTheDocument();
    expect(screen.queryByText('Marketing content')).not.toBeInTheDocument();
});
