// @flow
import {render, shallow} from 'enzyme';
import React from 'react';
import BlockToolbar from '../BlockToolbar';
import {setTranslations} from '../../../utils/Translator/Translator';

setTranslations({
    'sulu_admin.%count%_selected': '{count} selected',
    'sulu_admin.select_all': 'Select all',
    'sulu_admin.deselect_all': 'Deselect all',
    'sulu_admin.cancel': 'Cancel',
}, 'en');

test('Render a Breadcrumb', () => {
    const blockToolbar = render(
        <BlockToolbar
            actions={[
                {
                    label: 'Copy',
                    icon: 'su-copy',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Duplicate',
                    icon: 'su-duplicate',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Cut',
                    icon: 'su-cut',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Delete',
                    icon: 'su-trash-alt',
                    handleClick: jest.fn(),
                },
            ]}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );
    expect(blockToolbar).toMatchSnapshot();
});

test('Click cancel button', () => {
    const clickSpy = jest.fn();
    const blockToolbar = shallow(
        <BlockToolbar
            actions={[
                {
                    label: 'Copy',
                    icon: 'su-copy',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Duplicate',
                    icon: 'su-duplicate',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Cut',
                    icon: 'su-cut',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Delete',
                    icon: 'su-trash-alt',
                    handleClick: jest.fn(),
                },
            ]}
            allSelected={true}
            onCancel={clickSpy}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    expect(blockToolbar.find('button').last().text()).toBe('<Icon />Cancel');
    blockToolbar.find('button').last().simulate('click');

    expect(clickSpy).toHaveBeenCalled();
});

test('Click action button', () => {
    const clickSpy = jest.fn();
    const blockToolbar = shallow(
        <BlockToolbar
            actions={[
                {
                    label: 'Copy',
                    icon: 'su-copy',
                    handleClick: clickSpy,
                },
                {
                    label: 'Duplicate',
                    icon: 'su-duplicate',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Cut',
                    icon: 'su-cut',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Delete',
                    icon: 'su-trash-alt',
                    handleClick: jest.fn(),
                },
            ]}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    blockToolbar.find('button').at(0).simulate('click');

    expect(clickSpy).toHaveBeenCalled();
});

test('Click select all button', () => {
    const clickSpy = jest.fn();
    const blockToolbar = shallow(
        <BlockToolbar
            actions={[
                {
                    label: 'Copy',
                    icon: 'su-copy',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Duplicate',
                    icon: 'su-duplicate',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Cut',
                    icon: 'su-cut',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Delete',
                    icon: 'su-trash-alt',
                    handleClick: jest.fn(),
                },
            ]}
            allSelected={false}
            onCancel={jest.fn()}
            onSelectAll={clickSpy}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    blockToolbar.find('Checkbox').at(0).simulate('change');

    expect(clickSpy).toHaveBeenCalled();
});

test('Click un select all button', () => {
    const clickSpy = jest.fn();
    const blockToolbar = shallow(
        <BlockToolbar
            actions={[
                {
                    label: 'Copy',
                    icon: 'su-copy',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Duplicate',
                    icon: 'su-duplicate',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Cut',
                    icon: 'su-cut',
                    handleClick: jest.fn(),
                },
                {
                    label: 'Delete',
                    icon: 'su-trash-alt',
                    handleClick: jest.fn(),
                },
            ]}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={clickSpy}
            selectedCount={2}
        />
    );

    blockToolbar.find('Checkbox').at(0).simulate('change');

    expect(clickSpy).toHaveBeenCalled();
});
