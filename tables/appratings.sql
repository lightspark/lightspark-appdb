SELECT appVersion.appId, appVersion.versionId, appName, versionName, avg(score) as rating, count(appVersion.versionId) as hits
FROM appRating, appFamily, appVersion 
WHERE (appVersion.versionId = appRating.versionId
AND appFamily.appId = appVersion.appId) 
AND system = 'fake' AND rating = 5
GROUP BY appVersion.versionId
ORDER BY appName ASC
