// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import SmartContentItem from '../SmartContentItem';

test('Render item with only title', () => {
    render(<SmartContentItem item={{title: 'Only title'}} />);

    expect(screen.getByLabelText('Only title')).toBeInTheDocument();
});

test('Render item with title and draft with published state', () => {
    render(<SmartContentItem item={{title: 'Draft and published', publishedState: false, published: new Date()}} />);

    expect(screen.getByLabelText('Draft and published')).toBeInTheDocument();
    expect(document.querySelector('.published')).toBeInTheDocument();
    expect(document.querySelector('.draft')).toBeInTheDocument();
});

test('Render item with title and published state', () => {
    render(<SmartContentItem item={{title: 'Published', publishedState: true, published: new Date()}} />);

    expect(screen.getByLabelText('Published')).toBeInTheDocument();
    expect(document.querySelector('.publishIndicator')).not.toBeInTheDocument();
});

test('Render item with title and image', () => {
    render(<SmartContentItem item={{image: 'image.jpg', title: 'Image'}} />);

    expect(screen.getByLabelText('Image')).toBeInTheDocument();
    expect(document.querySelector('img')).toHaveAttribute('src', 'image.jpg');
});

test('Render item with additional columns except for id', () => {
    render(<SmartContentItem item={{id: 4, title: 'Title with URL', url: '/url', value: 'Test'}} />);

    expect(screen.getByLabelText('Title with URL')).toBeInTheDocument();
    expect(screen.getByLabelText('/url')).toBeInTheDocument();
    expect(screen.getByLabelText('Test')).toBeInTheDocument();
    expect(screen.queryByLabelText('4')).not.toBeInTheDocument();
});
