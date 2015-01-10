# Spoke Dockerfile for pxe

FROM            radial/spoke-base:latest
MAINTAINER      Brian Clements <radial@brianclements.net>

# Install packages
RUN echo 'Acquire::http { Proxy "http://172.17.42.1:3142"; };' >> /etc/apt/apt.conf.d/01proxy
ENV             DEBIAN_FRONTEND noninteractive
RUN             apt-get -q update && apt-get -qyV install \
                     dnsmasq wget syslinux host &&\
                apt-get clean

# Set Spoke ID
ENV             SPOKE_NAME pxe

# tunnable variables
ENV RELEASE alpha

COPY            /entrypoint.sh /entrypoint.sh
