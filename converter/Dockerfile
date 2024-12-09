FROM alpine

ARG CJ_USERNAME
ARG CJ_PASSWORD

ENV CJ_USERNAME=$CJ_USERNAME
ENV CJ_PASSWORD=$CJ_PASSWORD

RUN apk update && \
    apk add --no-cache curl && \
    rm -rf /var/cache/apk/*

COPY converter.sh /data/converter.sh

RUN chmod +x /data/converter.sh

WORKDIR /data

CMD ./converter.sh "$CJ_USERNAME" "$CJ_PASSWORD"
