---
layout: post
author: shaldengeki
date: 6 Jun 2013
title: A look into problems with over-recommended anime
---

I've looked into why certain anime seem to be overrepresented in the recs. Here's what I found:

- For some reason, Tenchi in Tokyo's less-important features seem to be exploding in magnitude, so they end up having a disproportionate effect on the overall rating. For instance, SSS's predicted score for Tenchi hovers around 4-6 for the first 20 or so features, and then shoots up to 10 and basically stays there afterwards. If you look at [Tenchi's features](http://llanim.us:6275/svd/animeFeatures) numbers 21-49 you'll see what I mean; the magnitude of Tenchi's feature is at least 6x that of its nearest neighbor for like thirty straight features. I'm not sure what's causing this, but I'm already penalizing for feature magnitude and I can just penalize more to compensate in the short term; maybe use a squared relationship instead of just a linear one.

- Karneval is less-obviously pathological. Its feature magnitudes are all pretty reasonable. The biggest thing it has going for it is a really high average rating: 8.5 with 450 unique users rating it. So that's probably why a lot of people are seeing it up there; it's the epitome of a "safe" rec. If you don't have extremely strong features in anything against it, then the predicted rating isn't going to move too much from that 8.5. This is the case for a lot of other series that are common on the rec lists, like Gintama' (9.13 with 19.9k users) or Ookami Kodomo (avg 8.75 with 9.9k users).

I'm starting to think that I should be using the [Wilson score](http://www.evanmiller.org/how-not-to-sort-by-average-rating.html) instead of an average for the baseline rating, like I'm already doing on the [SAT's Top Anime list on llanim.us](http://llanim.us/satTopAnime.php); clearly we're seeing "the amazon effect" happen where an item with one 10-rating ends up higher-ranked than an item with a billion ratings averaged at 9.9.

The problem is, if I take action against Tenchi in Tokyo by penalizing the magnitude of the features, we're going to end up with smaller personal features, which makes the role of the average rating larger, which will result in "safer" and less-personal recs. But we also don't want to over-emphasize the personal features, since the averages do provide a really useful signal as to what's generally considered good. So I'll have to figure something out here.

If you have examples of things that seem really out-of-place on your recs, [drop me a line](https://animurecs.com/users/shaldengeki) and I can take a look into it to make sure nothing is wrong on my end. And as always, let me know if you find something that could be done better on the site.