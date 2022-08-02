// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Mousetrap from 'mousetrap';
import AutoCompletePopover from '../AutoCompletePopover';

beforeEach(() => {
    Mousetrap.reset();
});

test('Popover should be hidden when open is set to false', () => {
    render(
        <div>Anchor Element</div>
    );

    const suggestions = [
        {id: 1, name: 'Test 1 (selector-1)'},
        {id: 2, name: 'Test 2 (selector-2)'},
    ];
    render(
        <AutoCompletePopover
            anchorElement={screen.getByText('Anchor Element')}
            onSelect={jest.fn()}
            open={false}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    );

    expect(screen.queryByText(/selector-1/)).not.toBeInTheDocument();
});

test('Popover should be shown when open is set to true', () => {
    render(
        <div>Anchor Element</div>
    );

    const suggestions = [
        {id: 1, name: 'Test 1 (selector-1)'},
        {id: 2, name: 'Test 2 (selector-2)'},
    ];
    render(
        <AutoCompletePopover
            anchorElement={screen.getByText('Anchor Element')}
            onSelect={jest.fn()}
            open={true}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    );

    expect(screen.getByText(/selector-1/)).toBeInTheDocument();
});

test('Render with highlighted suggestions', () => {
    render(
        <div>Anchor Element</div>
    );

    const suggestions = [
        {id: 1, name: 'Test 1 (selector-1)'},
        {id: 2, name: 'Test 2 (selector-2)'},
    ];
    render(
        <AutoCompletePopover
            anchorElement={screen.getByText('Anchor Element')}
            onSelect={jest.fn()}
            open={true}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    );

    expect(document.body).toMatchSnapshot();
});

test('Call onClose when Popover is closed', async() => {
    render(
        <div>Anchor Element</div>
    );

    const suggestions = [
        {id: 1, name: 'Test 1 (selector-1)'},
        {id: 2, name: 'Test 2 (selector-2)'},
    ];
    const closeSpy = jest.fn();
    render(
        <AutoCompletePopover
            anchorElement={screen.getByText('Anchor Element')}
            onClose={closeSpy()}
            onSelect={jest.fn()}
            open={true}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    );

    const user = userEvent.setup();
    await user.click(screen.getByTestId('backdrop'));
    expect(closeSpy).toBeCalledWith();
});

test('Call onSelect with clicked suggestion', async() => {
    render(
        <div>Anchor Element</div>
    );

    const suggestions = [
        {id: 1, name: 'Test 1 (selector-1)'},
        {id: 2, name: 'Test 2 (selector-2)'},
    ];
    const selectSpy = jest.fn();
    render(
        <AutoCompletePopover
            anchorElement={screen.getByText('Anchor Element')}
            onSelect={selectSpy}
            open={true}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    );

    const user = userEvent.setup();
    await user.click(screen.getByText(/selector-2/));
    expect(selectSpy).toBeCalledWith(suggestions[1]);
});

test('Should focus suggestions when pressing up and down key', async() => {
    render(
        <div>Anchor Element</div>
    );

    const suggestions = [
        {id: 1, name: 'Test 1 (selector-1)'},
        {id: 2, name: 'Test 2 (selector-2)'},
    ];
    render(
        <AutoCompletePopover
            anchorElement={screen.getByText('Anchor Element')}
            onSelect={jest.fn()}
            open={true}
            query="Test"
            searchProperties={['name']}
            suggestions={suggestions}
        />
    );
    const suggestionElements = screen.getAllByRole('button').slice(1); // first button is the popover backdrop

    expect(suggestionElements[0]).not.toHaveFocus();
    expect(suggestionElements[1]).not.toHaveFocus();

    Mousetrap.trigger('down');
    expect(suggestionElements[0]).toHaveFocus();
    expect(suggestionElements[1]).not.toHaveFocus();

    Mousetrap.trigger('down');
    expect(suggestionElements[0]).not.toHaveFocus();
    expect(suggestionElements[1]).toHaveFocus();

    Mousetrap.trigger('up');
    expect(suggestionElements[0]).toHaveFocus();
    expect(suggestionElements[1]).not.toHaveFocus();
});
