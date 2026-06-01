ALTER TABLE wiki_articles ADD FULLTEXT INDEX ft_articles (title, content);
ALTER TABLE theories ADD FULLTEXT INDEX ft_theories (title, content);
