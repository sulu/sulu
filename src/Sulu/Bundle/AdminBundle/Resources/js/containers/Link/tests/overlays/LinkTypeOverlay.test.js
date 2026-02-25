// @flow
import React from 'react';
import {render} from '@testing-library/react';
import LinkTypeOverlay from '../../overlays/LinkTypeOverlay';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../SingleSelection', () => jest.fn(() => null));

const options = {
    displayProperties: ['title'],
    emptyText: 'No page selected',
    icon: 'su-document',
    listAdapter: 'column_list',
    overlayTitle: 'Choose page',
    resourceKey: 'pages',
    targets: ['_blank', '_self', '_parent', '_top'],
};

test('Render overlay with minimal config', () => {
    const {baseElement} = render(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={options}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render overlay without options', () => {
    expect(() => render(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={undefined}
        />
    )).toThrow('The LinkTypeOverlay needs some options in order to work!');
});

test('Render overlay with query enabled', () => {
    const {baseElement} = render(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onQueryChange={jest.fn()}
            open={true}
            options={options}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render overlay with anchor enabled', () => {
    const {baseElement} = render(
        <LinkTypeOverlay
            href={undefined}
            onAnchorChange={jest.fn()}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={options}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render overlay with target enabled', () => {
    const {baseElement} = render(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTargetChange={jest.fn()}
            open={true}
            options={options}
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render overlay with title enabled', () => {
    const {baseElement} = render(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={options}
        />
    );

    expect(baseElement).toMatchSnapshot();
});
