<rss version="2.0"
     xmlns:content="http://purl.org/rss/1.0/modules/content/"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:media="http://search.yahoo.com/mrss/"
     xmlns:atom="http://www.w3.org/2005/Atom"
>
    <channel>
        <title>{{ $title }}</title>
        <link>{{ $link }}</link>
        <atom:link href="{{ $feed_link ?? $link }}" rel="self" type="application/rss+xml"/>
        <description>{{ $description }}</description>
        <generator>Feedable</generator>
        @if(filled($language))
            <language>{{ $language }}</language>
        @endif
        @if(filled($image))
            <image>
                <url>{{ $image }}</url>
                <title>{{ $title }}</title>
                <link>{{ $link }}</link>
            </image>
        @endif
        <ttl>{{ $ttl }}</ttl>
        @foreach($items as $item)
            <item>
                <title>{{ data_get($item, 'title') }}</title>
                <link>{{ data_get($item, 'url') }}</link>
                <guid isPermaLink="false">{{ data_get($item, 'id') }}</guid>
                @if(filled(data_get($item,'date_published')))
                    <pubDate>{{ data_get($item,'date_published') }}</pubDate>
                @endif
                @if(filled(data_get($item, 'content_html')))
                    <description><![CDATA[{!! data_get($item, 'content_html') !!}]]></description>
                @endif
                @if(filled(data_get($item, 'content_text')))
                    <description>{{ data_get($item, 'content_text') }}</description>
                @endif
                @if(filled(data_get($item, 'summary')))
                    <description>{{ data_get($item, 'summary') }}</description>
                @endif
                @if(filled(data_get($item, 'content')))
                    <content:encoded><![CDATA[{!! data_get($item, 'content') !!}]]></content:encoded>
                @endif
                @if(filled(data_get($item, 'authors')))
                    <dc:creator>{{ implode(', ', data_get($item, 'authors.*.name')) }}</dc:creator>
                @endif
                @if(is_array(data_get($item, 'tags')))
                    @foreach(data_get($item, 'tags') as $category)
                        <category>{{ $category }}</category>
                    @endforeach
                @endif
                @if(filled(data_get($item, 'image')))
                    <media:thumbnail>{{ data_get($item, 'image') }}</media:thumbnail>
                @endif
                @if(is_array(data_get($item, 'attachments')))
                    @foreach(data_get($item, 'attachments') as $attachment)
                        <enclosure url="{{ data_get($attachment, 'url') }}" type="{{ data_get($attachment, 'mime_type') }}" length="{{ data_get($attachment, 'size_in_bytes', 0) }}"/>
                    @endforeach
                @endif
            </item>
        @endforeach
    </channel>
</rss>
