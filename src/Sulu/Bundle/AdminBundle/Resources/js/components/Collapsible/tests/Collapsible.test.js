// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Collapsible from '../Collapsible';

test('Render an expanded collapsible with title, subtitle, extra node and handle', () => {
    const {container} = render(
        <Collapsible
            actions={[{icon: 'su-trash-alt', label: 'Delete', onClick: jest.fn()}]}
            expanded={true}
            extra={<span>4/7</span>}
            handle={<span>Handle</span>}
            onCollapse={jest.fn()}
            onExpand={jest.fn()}
            subtitle="3 attributes"
            title="General"
        >
            Some collapsible content
        </Collapsible>
    );

    expect(container).toMatchSnapshot();
});

test('Render title and subtitle', () => {
    render(
        <Collapsible subtitle="3 attributes" title="General">
            Some collapsible content
        </Collapsible>
    );

    expect(screen.getByText('General')).toBeInTheDocument();
    expect(screen.getByText('3 attributes')).toBeInTheDocument();
});

test('Render the children and the collapse icon when expanded', () => {
    render(
        <Collapsible expanded={true} onCollapse={jest.fn()} onExpand={jest.fn()} title="General">
            Some collapsible content
        </Collapsible>
    );

    expect(screen.getByText('Some collapsible content')).toBeInTheDocument();
    expect(screen.getByLabelText('su-collapse-vertical')).toBeInTheDocument();
});

test('Do not render the children and show the expand icon when collapsed', () => {
    render(
        <Collapsible expanded={false} onCollapse={jest.fn()} onExpand={jest.fn()} title="General">
            Some collapsible content
        </Collapsible>
    );

    expect(screen.queryByText('Some collapsible content')).not.toBeInTheDocument();
    expect(screen.getByLabelText('su-expand-vertical')).toBeInTheDocument();
});

test('Render the children and no toggle when no expand and collapse callbacks are given', () => {
    render(
        <Collapsible expanded={false} title="General">
            Some collapsible content
        </Collapsible>
    );

    expect(screen.getByText('Some collapsible content')).toBeInTheDocument();
    expect(screen.queryByLabelText('su-collapse-vertical')).not.toBeInTheDocument();
    expect(screen.queryByLabelText('su-expand-vertical')).not.toBeInTheDocument();
});

test('Clicking the toggle of an expanded collapsible should call the onCollapse callback', async() => {
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <Collapsible expanded={true} onCollapse={collapseSpy} onExpand={expandSpy} title="General">
            Some collapsible content
        </Collapsible>
    );

    await user.click(screen.getByLabelText('su-collapse-vertical'));

    expect(collapseSpy).toHaveBeenCalledWith();
    expect(expandSpy).not.toHaveBeenCalled();
});

test('Clicking the toggle of a collapsed collapsible should call the onExpand callback once', async() => {
    const collapseSpy = jest.fn();
    const expandSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <Collapsible expanded={false} onCollapse={collapseSpy} onExpand={expandSpy} title="General">
            Some collapsible content
        </Collapsible>
    );

    await user.click(screen.getByLabelText('su-expand-vertical'));

    expect(expandSpy).toHaveBeenCalledTimes(1);
    expect(collapseSpy).not.toHaveBeenCalled();
});

test('Clicking a collapsed collapsible should call the onExpand callback', async() => {
    const expandSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <Collapsible expanded={false} onCollapse={jest.fn()} onExpand={expandSpy} title="General">
            Some collapsible content
        </Collapsible>
    );

    await user.click(screen.getByText('General'));

    expect(expandSpy).toHaveBeenCalledWith();
});

test('Clicking an expanded collapsible should not call the onExpand callback', async() => {
    const expandSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <Collapsible expanded={true} onCollapse={jest.fn()} onExpand={expandSpy} title="General">
            Some collapsible content
        </Collapsible>
    );

    await user.click(screen.getByText('General'));

    expect(expandSpy).not.toHaveBeenCalled();
});

test('Clicking an action should call its callback without expanding the collapsible', async() => {
    const deleteSpy = jest.fn();
    const expandSpy = jest.fn();
    const user = userEvent.setup();

    render(
        <Collapsible
            actions={[{icon: 'su-trash-alt', label: 'Delete', onClick: deleteSpy}]}
            expanded={false}
            onCollapse={jest.fn()}
            onExpand={expandSpy}
            title="General"
        >
            Some collapsible content
        </Collapsible>
    );

    await user.click(screen.getByLabelText('Delete'));

    expect(deleteSpy).toHaveBeenCalledWith();
    expect(expandSpy).not.toHaveBeenCalled();
});

test('Render the extra node and the handle in their slots', () => {
    render(
        <Collapsible
            expanded={true}
            extra={<span>4/7</span>}
            handle={<span>Handle</span>}
            title="General"
        >
            Some collapsible content
        </Collapsible>
    );

    expect(screen.getByText('4/7')).toBeInTheDocument();
    expect(screen.getByText('Handle')).toBeInTheDocument();
});
